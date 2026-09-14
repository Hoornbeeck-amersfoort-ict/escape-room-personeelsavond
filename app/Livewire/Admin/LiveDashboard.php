<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\AdjustScore;
use App\Actions\Admin\AssignRoomManually;
use App\Actions\Admin\ResetRoomSession;
use App\Actions\Admin\ToggleTeamActive;
use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\GameService;
use App\Services\GameTimerService;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LiveDashboard extends Component
{
    public Game $game;

    public string $tab = 'teams';

    public ?int $manualAssignTeamId = null;

    public ?int $resettingSessionId = null;

    public ?int $adjustingScoreSessionId = null;

    public int $adjustScoreValue = 0;

    public function mount(Game $game): void
    {
        $this->game = $game;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function openManualAssign(int $teamId): void
    {
        $this->manualAssignTeamId = $teamId;
    }

    public function cancelManualAssign(): void
    {
        $this->manualAssignTeamId = null;
    }

    public function assignRoom(int $roomId, AssignRoomManually $action): void
    {
        if ($this->manualAssignTeamId === null) {
            return;
        }

        $team = Team::query()->where('game_id', $this->game->id)->findOrFail($this->manualAssignTeamId);
        $room = Room::query()->where('game_id', $this->game->id)->findOrFail($roomId);

        $action->execute(Auth::guard('web')->user(), $team, $room);

        $this->manualAssignTeamId = null;
    }

    public function confirmResetSession(int $sessionId): void
    {
        $this->resettingSessionId = $sessionId;
    }

    public function cancelResetSession(): void
    {
        $this->resettingSessionId = null;
    }

    public function resetSession(ResetRoomSession $action): void
    {
        if ($this->resettingSessionId === null) {
            return;
        }

        $session = RoomSession::query()->where('game_id', $this->game->id)->findOrFail($this->resettingSessionId);

        $action->execute(Auth::guard('web')->user(), $session);

        $this->resettingSessionId = null;
    }

    public function openAdjustScore(int $sessionId): void
    {
        $session = RoomSession::query()->where('game_id', $this->game->id)->findOrFail($sessionId);

        $this->adjustingScoreSessionId = $sessionId;
        $this->adjustScoreValue = $session->points;
    }

    public function cancelAdjustScore(): void
    {
        $this->adjustingScoreSessionId = null;
    }

    public function saveAdjustScore(AdjustScore $action): void
    {
        if ($this->adjustingScoreSessionId === null) {
            return;
        }

        $this->validate(['adjustScoreValue' => ['required', 'integer', 'min:0', 'max:3']]);

        $session = RoomSession::query()->where('game_id', $this->game->id)->findOrFail($this->adjustingScoreSessionId);

        $action->execute(Auth::guard('web')->user(), $session, $this->adjustScoreValue);

        $this->adjustingScoreSessionId = null;
    }

    public function toggleTeamActive(int $teamId, ToggleTeamActive $action): void
    {
        $team = Team::query()->where('game_id', $this->game->id)->findOrFail($teamId);

        $action->execute(Auth::guard('web')->user(), $team);
    }

    public function render()
    {
        $game = app(GameService::class)->finalizeIfEnded($this->game->fresh());
        $this->game = $game;

        $timer = app(GameTimerService::class);

        $teams = Team::query()
            ->where('game_id', $game->id)
            // Deliberately unfiltered: an "assigned" session (not started yet,
            // team is still walking over) must still show up as the team's
            // current room ("Onderweg") — filtering by started_at here would
            // hide it and make every walking team look like it has no room.
            ->with(['roomSessions' => fn ($query) => $query->with('room')])
            ->get()
            ->sortByNatural('name')
            ->map(function (Team $team) use ($game, $timer) {
                $current = $team->roomSessions
                    ->whereIn('status', RoomSessionStatus::occupying())
                    ->sortByDesc('id')
                    ->first();

                return [
                    'team' => $team,
                    'current_session' => $current,
                    'current_room' => $current?->room,
                    'points' => $team->roomSessions->sum('points'),
                    'active_seconds' => $timer->totalActiveSecondsForTeam($team, $game),
                    'rooms_played' => $team->roomSessions
                        ->filter(fn ($session) => in_array($session->status, RoomSessionStatus::played(), true))
                        ->count(),
                ];
            });

        $rooms = Room::query()
            ->where('game_id', $game->id)
            ->withCount([
                'roomSessions as active_teams_count' => fn ($query) => $query->whereIn(
                    'status',
                    array_map(fn ($status) => $status->value, RoomSessionStatus::occupying())
                ),
            ])
            ->get()
            ->sortByNatural('name');

        $standings = $this->tab === 'leaderboard'
            ? app(LeaderboardService::class)->standings($game)
            : collect();

        $allRooms = $this->manualAssignTeamId !== null
            ? Room::query()->where('game_id', $game->id)->where('active', true)->get()->sortByNatural('name')
            : collect();

        return view('livewire.admin.live-dashboard', [
            'game' => $game,
            'teamsOverview' => $teams,
            'roomsOverview' => $rooms,
            'standings' => $standings,
            'remainingEventSeconds' => $timer->remainingEventSeconds($game),
            'allRoomsForAssign' => $allRooms,
        ])->layout('components.layouts.admin', ['game' => $game]);
    }
}
