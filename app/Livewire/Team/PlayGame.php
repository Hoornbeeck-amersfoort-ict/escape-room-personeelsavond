<?php

namespace App\Livewire\Team;

use App\Actions\RoomSession\GiveUpRoomSession;
use App\Actions\RoomSession\StartRoomSession;
use App\Actions\RoomSession\SubmitAnswer;
use App\Enums\GameStatus;
use App\Enums\RoomSessionStatus;
use App\Exceptions\RoomSessionStateException;
use App\Models\Game;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\GameService;
use App\Services\GameTimerService;
use App\Services\LeaderboardService;
use App\Services\RoomAssignmentService;
use App\Support\RoomAssignmentOutcome;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PlayGame extends Component
{
    public string $answer = '';

    public bool $confirmingGiveUp = false;

    /** @var array{type: string, points?: int, attemptsRemaining?: int, message?: string}|null */
    public ?array $feedback = null;

    public function mount(): void
    {
        // Opportunistically attempt an assignment on first load so a team that
        // has never had a session yet (fresh login) gets one immediately.
        $team = $this->team();
        $game = $this->resolveGame($team);

        if ($game->isRunning() && $this->currentSession($team) === null) {
            app(RoomAssignmentService::class)->assignNextRoom($team, $game);
        }
    }

    public function startRoom(StartRoomSession $action): void
    {
        $team = $this->team();
        $game = $this->resolveGame($team);
        $session = $this->currentSession($team);

        if ($session === null) {
            return;
        }

        Gate::forUser($team)->authorize('start', $session);

        try {
            $action->execute($session, $team, $game);
        } catch (RoomSessionStateException $exception) {
            $this->feedback = ['type' => 'error', 'message' => $exception->getMessage()];
        }
    }

    public function submitAnswer(SubmitAnswer $action): void
    {
        $this->validate(['answer' => ['required', 'string', 'max:255']]);

        $team = $this->team();
        $game = $this->resolveGame($team);
        $session = $this->currentSession($team);

        if ($session === null || $session->status !== RoomSessionStatus::Active) {
            $this->answer = '';

            return;
        }

        Gate::forUser($team)->authorize('submitAnswer', $session);

        try {
            $result = $action->execute($session, $team, $game, $this->answer);
        } catch (RoomSessionStateException $exception) {
            $this->answer = '';
            $this->feedback = ['type' => 'error', 'message' => $exception->getMessage()];

            return;
        }

        $this->answer = '';

        if ($result->correct) {
            $this->feedback = [
                'type' => 'correct',
                'points' => $result->session->points,
                ...$this->assignmentFeedback($result->nextAssignment),
            ];
        } elseif ($result->isRoomFinished()) {
            $this->feedback = [
                'type' => 'failed',
                ...$this->assignmentFeedback($result->nextAssignment),
            ];
        } else {
            $this->feedback = ['type' => 'incorrect', 'attemptsRemaining' => $result->attemptsRemaining];
        }
    }

    public function confirmGiveUp(): void
    {
        $this->confirmingGiveUp = true;
    }

    public function cancelGiveUp(): void
    {
        $this->confirmingGiveUp = false;
    }

    public function giveUp(GiveUpRoomSession $action): void
    {
        $team = $this->team();
        $game = $this->resolveGame($team);
        $session = $this->currentSession($team);

        $this->confirmingGiveUp = false;

        if ($session === null) {
            return;
        }

        Gate::forUser($team)->authorize('giveUp', $session);

        try {
            $outcome = $action->execute($session, $team, $game);
        } catch (RoomSessionStateException $exception) {
            $this->feedback = ['type' => 'error', 'message' => $exception->getMessage()];

            return;
        }

        $this->feedback = ['type' => 'given_up', ...$this->assignmentFeedback($outcome)];
    }

    public function continueAfterFeedback(): void
    {
        $this->feedback = null;
    }

    /**
     * @return array{nextRoomName: ?string, allRoomsPlayed: bool, waiting: bool}
     */
    private function assignmentFeedback(?RoomAssignmentOutcome $outcome): array
    {
        return [
            'nextRoomName' => $outcome?->session?->room?->name,
            'allRoomsPlayed' => $outcome?->allRoomsPlayed ?? false,
            'waiting' => $outcome?->isWaiting() ?? false,
        ];
    }

    public function render()
    {
        $team = $this->team();
        $game = $this->resolveGame($team);

        // A wrong-but-not-final attempt is shown inline on top of the still-active
        // room, everything else (correct/failed/given-up/error) is a full interstitial
        // that replaces the room view since the underlying session already moved on.
        $isInterstitial = $this->feedback !== null && $this->feedback['type'] !== 'incorrect';
        $session = $isInterstitial ? null : $this->currentSession($team);

        $viewData = [
            'team' => $team,
            'game' => $game,
            'session' => $session,
            'feedback' => $this->feedback,
            'remainingEventSeconds' => app(GameTimerService::class)->remainingEventSeconds($game),
        ];

        if ($game->status === GameStatus::Draft) {
            $viewData['state'] = 'not_started';
        } elseif ($isInterstitial) {
            $viewData['state'] = 'feedback';
        } elseif ($game->isFinished()) {
            $viewData['state'] = 'finished';
            $viewData['ownResult'] = $this->ownResult($team, $game);
        } elseif ($session === null) {
            $outcome = app(RoomAssignmentService::class)->assignNextRoom($team, $game);
            $viewData['state'] = match (true) {
                $outcome->wasAssigned() => 'assigned',
                $outcome->allRoomsPlayed => 'all_played',
                default => 'waiting',
            };
            $viewData['session'] = $outcome->session;
        } elseif ($session->status === RoomSessionStatus::Assigned) {
            $viewData['state'] = 'assigned';
        } else {
            $viewData['state'] = 'active';
            $viewData['activeDurationSeconds'] = $session->activeDurationSeconds();
            $viewData['attemptsUsed'] = $session->attemptsUsed();
        }

        return view('livewire.team.play-game', $viewData)->layout('components.layouts.team');
    }

    private function team(): Team
    {
        /** @var Team $team */
        $team = Auth::guard('team')->user();

        return $team;
    }

    private function resolveGame(Team $team): Game
    {
        $game = $team->game()->firstOrFail();

        return app(GameService::class)->finalizeIfEnded($game);
    }

    private function currentSession(Team $team): ?RoomSession
    {
        return RoomSession::query()
            ->where('team_id', $team->id)
            ->whereIn('status', [RoomSessionStatus::Assigned->value, RoomSessionStatus::Active->value])
            ->with('room')
            ->latest('id')
            ->first();
    }

    /**
     * @return array{points: int, active_seconds: int, rank: int, total_teams: int}
     */
    private function ownResult(Team $team, Game $game): array
    {
        $standings = app(LeaderboardService::class)->standings($game);
        $own = $standings->first(fn (array $row) => $row['team']->is($team));

        return [
            'points' => $own['points'] ?? 0,
            'active_seconds' => $own['active_seconds'] ?? 0,
            'rooms_played' => $own['rooms_played'] ?? 0,
            'rank' => $own['rank'] ?? $standings->count(),
            'total_teams' => $standings->count(),
        ];
    }
}
