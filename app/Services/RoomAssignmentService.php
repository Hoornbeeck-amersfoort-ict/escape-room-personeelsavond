<?php

namespace App\Services;

use App\Enums\RoomSessionStatus;
use App\Events\RoomAssigned;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Support\RoomAssignmentOutcome;
use Illuminate\Support\Facades\DB;

/**
 * The single source of truth for "give this team the best next room".
 *
 * Every place in the application that needs to hand a team a room — first
 * login, after completing/failing/giving up a room, or an admin's manual
 * "assign next available room" action — must go through this service so the
 * selection rules never drift into five slightly different implementations.
 *
 * Selection rules (see app spec §5):
 *  1. Rooms the team already played (completed/failed/given_up) are excluded forever.
 *  2. Among the remaining active rooms, prefer the one(s) with the fewest teams
 *     currently occupying them (assigned or active). An empty room has 0 occupants,
 *     which is automatically the minimum, so "prefer empty rooms" falls out of the
 *     same rule as "prefer least busy" rather than needing separate branches.
 *  3. Ties are broken at random.
 */
class RoomAssignmentService
{
    /**
     * Assign the next room to a team, atomically. Safe to call concurrently for
     * different teams competing for the same rooms, and idempotent if called
     * twice for a team that already has an open session (e.g. a second device).
     */
    public function assignNextRoom(Team $team, Game $game): RoomAssignmentOutcome
    {
        $outcome = DB::transaction(function () use ($team, $game) {
            // Lock the team row so two near-simultaneous requests for the same
            // team (double tap, two devices) are strictly serialized.
            $lockedTeam = Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            $existing = RoomSession::query()
                ->where('team_id', $lockedTeam->id)
                ->whereIn('status', $this->occupyingValues())
                ->latest('id')
                ->first();

            if ($existing) {
                return RoomAssignmentOutcome::assigned($existing);
            }

            $playedRoomIds = RoomSession::query()
                ->where('team_id', $lockedTeam->id)
                ->whereIn('status', $this->playedValues())
                ->pluck('room_id');

            $anyUnplayedRoomExists = Room::query()
                ->where('game_id', $game->id)
                ->whereNotIn('id', $playedRoomIds)
                ->exists();

            if (! $anyUnplayedRoomExists) {
                return RoomAssignmentOutcome::allRoomsPlayed();
            }

            $candidateRooms = Room::query()
                ->where('game_id', $game->id)
                ->where('active', true)
                ->whereNotIn('id', $playedRoomIds)
                ->lockForUpdate()
                ->get();

            if ($candidateRooms->isEmpty()) {
                // Unplayed rooms exist, but none of them are currently active
                // (e.g. an admin temporarily disabled them). Team waits it out.
                return RoomAssignmentOutcome::waiting();
            }

            $occupancy = RoomSession::query()
                ->whereIn('room_id', $candidateRooms->pluck('id'))
                ->whereIn('status', $this->occupyingValues())
                ->lockForUpdate()
                ->get()
                ->countBy('room_id');

            $minOccupancy = $candidateRooms->min(fn (Room $room) => $occupancy->get($room->id, 0));

            $leastBusyRooms = $candidateRooms->filter(
                fn (Room $room) => $occupancy->get($room->id, 0) === $minOccupancy
            );

            /** @var Room $chosenRoom */
            $chosenRoom = $leastBusyRooms->random();

            $session = RoomSession::create([
                'game_id' => $game->id,
                'team_id' => $lockedTeam->id,
                'room_id' => $chosenRoom->id,
                'status' => RoomSessionStatus::Assigned,
            ]);

            return RoomAssignmentOutcome::assigned($session);
        }, attempts: 5);

        if ($outcome->wasAssigned() && $outcome->session->wasRecentlyCreated) {
            RoomAssigned::dispatch($outcome->session);
        }

        return $outcome;
    }

    /**
     * Admin override: assign a specific room to a team regardless of the
     * balancing algorithm. Still funnels through this service so there is one
     * place that knows how to open a room session, and still refuses to double
     * assign a team that already has an open session.
     */
    public function assignSpecificRoom(Team $team, Room $room): RoomSession
    {
        return DB::transaction(function () use ($team, $room) {
            $lockedTeam = Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            $existing = RoomSession::query()
                ->where('team_id', $lockedTeam->id)
                ->whereIn('status', $this->occupyingValues())
                ->latest('id')
                ->first();

            if ($existing) {
                $existing->delete();
            }

            $session = RoomSession::create([
                'game_id' => $room->game_id,
                'team_id' => $lockedTeam->id,
                'room_id' => $room->id,
                'status' => RoomSessionStatus::Assigned,
            ]);

            RoomAssigned::dispatch($session);

            return $session;
        }, attempts: 5);
    }

    private function occupyingValues(): array
    {
        return array_map(fn (RoomSessionStatus $status) => $status->value, RoomSessionStatus::occupying());
    }

    private function playedValues(): array
    {
        return array_map(fn (RoomSessionStatus $status) => $status->value, RoomSessionStatus::played());
    }
}
