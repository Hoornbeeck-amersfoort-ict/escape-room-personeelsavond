<?php

namespace App\Services;

use App\Database;
use App\Room;
use App\RoomSession;
use App\Team;

/**
 * Single source of truth for "give this team the best next room".
 * Rules: never repeat a room, prefer the least-occupied active room,
 * random tie-break. Ported straight from the Laravel RoomAssignmentService.
 */
class RoomAssignmentService
{
    public const ASSIGNED = 'assigned';

    public const WAITING = 'waiting';

    public const ALL_ROOMS_PLAYED = 'all_rooms_played';

    /** @return array{outcome: string, session?: array} */
    public function assignNextRoom(array $team, array $game): array
    {
        return Database::transaction(function () use ($team, $game) {
            $existing = Team::currentRoomSession((int) $team['id']);

            if ($existing) {
                return ['outcome' => self::ASSIGNED, 'session' => $existing];
            }

            $playedRoomIds = Team::playedRoomIds((int) $team['id']);

            $allRooms = Room::where(['game_id' => $game['id']]);
            $unplayedRooms = array_filter($allRooms, fn ($r) => ! in_array((int) $r['id'], $playedRoomIds, true));

            if ($unplayedRooms === []) {
                return ['outcome' => self::ALL_ROOMS_PLAYED];
            }

            // Een exclusieve kamer die bezet is telt niet mee: het team wacht
            // liever even dan dat er twee teams in dezelfde kamer staan.
            $candidateRooms = array_values(array_filter(
                $unplayedRooms,
                fn ($r) => (int) $r['active'] === 1 && ! Room::isFull($r)
            ));

            if ($candidateRooms === []) {
                return ['outcome' => self::WAITING];
            }

            $occupancy = [];
            foreach ($candidateRooms as $room) {
                $occupancy[$room['id']] = Room::activeTeamsCount((int) $room['id']);
            }

            $minOccupancy = min($occupancy);
            $leastBusy = array_values(array_filter($candidateRooms, fn ($r) => $occupancy[$r['id']] === $minOccupancy));

            $chosenRoom = $leastBusy[array_rand($leastBusy)];

            $sessionId = RoomSession::insert([
                'game_id' => $game['id'],
                'team_id' => $team['id'],
                'room_id' => $chosenRoom['id'],
                'status' => 'assigned',
            ]);

            return ['outcome' => self::ASSIGNED, 'session' => RoomSession::find($sessionId)];
        });
    }

    /** Admin override: assign a specific room regardless of the balancing algorithm. */
    public function assignSpecificRoom(array $team, array $room): array
    {
        return Database::transaction(function () use ($team, $room) {
            $existing = Team::currentRoomSession((int) $team['id']);
            if ($existing) {
                RoomSession::delete((int) $existing['id']);
            }

            $sessionId = RoomSession::insert([
                'game_id' => $room['game_id'],
                'team_id' => $team['id'],
                'room_id' => $room['id'],
                'status' => 'assigned',
            ]);

            return RoomSession::find($sessionId);
        });
    }
}
