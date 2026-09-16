<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Game;
use App\Room;
use App\RoomSession;
use App\Services\GameService;
use App\Services\GameTimerService;
use App\Services\LeaderboardService;
use App\Services\RoomAssignmentService;
use App\Services\RoomSessionActions;
use App\View;
use RuntimeException;

class PlayController
{
    public function show(): void
    {
        $team = Auth::team();
        $game = Game::find((int) $team['game_id']);
        $game = (new GameService)->finalizeIfEnded($game);

        $feedback = $_SESSION['play_feedback'] ?? null;
        unset($_SESSION['play_feedback']);

        $viewData = [
            'team' => $team,
            'game' => $game,
            'feedback' => $feedback,
            'remainingEventSeconds' => (new GameTimerService)->remainingEventSeconds($game),
        ];

        if ($game['status'] === 'draft') {
            $viewData['state'] = 'not_started';
        } elseif ($feedback !== null && $feedback['type'] !== 'incorrect') {
            // Een fout antwoord krijgt geen apart scherm: het team blijft in de
            // kamer, met de foutmelding en het aantal resterende pogingen.
            $viewData['state'] = 'feedback';
        } elseif ($game['status'] === 'finished') {
            $viewData['state'] = 'finished';
            $viewData['ownResult'] = $this->ownResult($team, $game);
        } else {
            $session = $this->currentSession((int) $team['id']);

            if ($session === null) {
                $outcome = (new RoomAssignmentService)->assignNextRoom($team, $game);
                $viewData['state'] = match ($outcome['outcome']) {
                    RoomAssignmentService::ASSIGNED => 'assigned',
                    RoomAssignmentService::ALL_ROOMS_PLAYED => 'all_played',
                    default => 'waiting',
                };
                $session = $outcome['session'] ?? null;
                $viewData['session'] = $session;
                $viewData['room'] = $session ? Room::find((int) $session['room_id']) : null;
            } elseif ($session['status'] === 'assigned') {
                $viewData['state'] = 'assigned';
                $viewData['session'] = $session;
                $viewData['room'] = Room::find((int) $session['room_id']);
            } else {
                $viewData['state'] = 'active';
                $viewData['session'] = $session;
                $viewData['room'] = Room::find((int) $session['room_id']);
                $viewData['activeDurationSeconds'] = RoomSession::activeDurationSeconds($session);
                $viewData['attemptsUsed'] = RoomSession::attemptsUsed((int) $session['id']);
            }
        }

        echo View::render('team/play', $viewData);
    }

    public function startRoom(): void
    {
        $this->guardPost();
        $team = Auth::team();
        $game = Game::find((int) $team['game_id']);
        $session = $this->currentSession((int) $team['id']);

        if ($session !== null) {
            try {
                (new RoomSessionActions)->start($session, $team, $game);
            } catch (RuntimeException $e) {
                $_SESSION['play_feedback'] = ['type' => 'error', 'message' => $e->getMessage()];
            }
        }

        header('Location: /play');
    }

    public function submitAnswer(): void
    {
        $this->guardPost();
        $team = Auth::team();
        $game = Game::find((int) $team['game_id']);
        $session = $this->currentSession((int) $team['id']);
        $answer = trim((string) ($_POST['answer'] ?? ''));

        if ($session === null || $session['status'] !== 'active' || $answer === '') {
            header('Location: /play');

            return;
        }

        $room = Room::find((int) $session['room_id']);

        try {
            $result = (new RoomSessionActions)->submitAnswer($session, $team, $game, $answer, $room);
        } catch (RuntimeException $e) {
            $_SESSION['play_feedback'] = ['type' => 'error', 'message' => $e->getMessage()];
            header('Location: /play');

            return;
        }

        if ($result['correct']) {
            $_SESSION['play_feedback'] = array_merge(
                ['type' => 'correct', 'points' => $result['session']['points']],
                $this->assignmentFeedback($result['next_assignment'])
            );
        } elseif ($result['attempts_remaining'] === 0 && $result['session']['status'] === 'failed') {
            $_SESSION['play_feedback'] = array_merge(
                ['type' => 'failed'],
                $this->assignmentFeedback($result['next_assignment'])
            );
        } else {
            $_SESSION['play_feedback'] = ['type' => 'incorrect', 'attempts_remaining' => $result['attempts_remaining']];
        }

        header('Location: /play');
    }

    public function giveUp(): void
    {
        $this->guardPost();
        $team = Auth::team();
        $game = Game::find((int) $team['game_id']);
        $session = $this->currentSession((int) $team['id']);

        if ($session !== null) {
            try {
                $outcome = (new RoomSessionActions)->giveUp($session, $team, $game);
                $_SESSION['play_feedback'] = array_merge(['type' => 'given_up'], $this->assignmentFeedback($outcome));
            } catch (RuntimeException $e) {
                $_SESSION['play_feedback'] = ['type' => 'error', 'message' => $e->getMessage()];
            }
        }

        header('Location: /play');
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }

    private function currentSession(int $teamId): ?array
    {
        $sessions = RoomSession::where(['team_id' => $teamId, 'status' => RoomSession::OCCUPYING], 'id DESC');

        return $sessions[0] ?? null;
    }

    private function assignmentFeedback(?array $outcome): array
    {
        $nextRoomName = null;
        if ($outcome && ($outcome['outcome'] ?? null) === RoomAssignmentService::ASSIGNED && isset($outcome['session'])) {
            $room = Room::find((int) $outcome['session']['room_id']);
            $nextRoomName = $room['name'] ?? null;
        }

        return [
            'next_room_name' => $nextRoomName,
            'all_rooms_played' => ($outcome['outcome'] ?? null) === RoomAssignmentService::ALL_ROOMS_PLAYED,
            'waiting' => ($outcome['outcome'] ?? null) === RoomAssignmentService::WAITING,
        ];
    }

    private function ownResult(array $team, array $game): array
    {
        $standings = (new LeaderboardService)->standings($game);
        $own = null;
        foreach ($standings as $row) {
            if ((int) $row['team']['id'] === (int) $team['id']) {
                $own = $row;
                break;
            }
        }

        return [
            'points' => $own['points'] ?? 0,
            'active_seconds' => $own['active_seconds'] ?? 0,
            'rooms_played' => $own['rooms_played'] ?? 0,
            'rank' => $own['rank'] ?? count($standings),
            'total_teams' => count($standings),
        ];
    }
}
