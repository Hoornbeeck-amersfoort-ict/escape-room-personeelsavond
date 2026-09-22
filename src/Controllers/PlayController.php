<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Game;
use App\Room;
use App\RoomSession;
use App\Services\GameService;
use App\Services\GameTimerService;
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

        $viewData['fingerprint'] = $this->fingerprint($team);

        echo View::render('team/play', $viewData);
    }

    /**
     * Vertelt de pagina of er iets veranderd is (nieuwe kamer, gereset, spel
     * gestart of afgelopen). Zo hoeft er niet blind elke paar seconden
     * herladen te worden: de pagina ververst alleen als het nodig is.
     */
    public function status(): void
    {
        header('Content-Type: application/json');
        $team = Auth::team();

        if (! $team) {
            echo json_encode(['fingerprint' => 'uitgelogd']);

            return;
        }

        echo json_encode(['fingerprint' => $this->fingerprint($team)]);
    }

    private function fingerprint(array $team): string
    {
        $game = Game::find((int) $team['game_id']);
        $game = (new GameService)->finalizeIfEnded($game);
        $session = $this->currentSession((int) $team['id']);

        $delen = [
            $game['status'],
            (string) $game['end_time'],
            $session['id'] ?? '-',
            $session['status'] ?? '-',
            $session['room_id'] ?? '-',
        ];

        // Zonder kamer staat het team te wachten (bijvoorbeeld op een kamer waar
        // maar één team in mag). Dan telt de bezetting mee, zodat het wachtscherm
        // vanzelf doorgaat zodra er een kamer vrijkomt.
        if ($session === null) {
            $bezet = array_map(
                fn ($s) => (int) $s['room_id'],
                RoomSession::where(['game_id' => $game['id'], 'status' => RoomSession::OCCUPYING])
            );
            sort($bezet);

            $kamers = array_map(
                fn ($r) => $r['id'].':'.$r['active'].':'.$r['exclusive'],
                Room::where(['game_id' => $game['id']])
            );

            $delen[] = md5(implode(',', $bezet).'|'.implode(',', $kamers));
        }

        return implode('|', $delen);
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

    /**
     * Alleen de eigen cijfers. Bewust niet via de ranglijst: de klassering is
     * voor de organisator, dus die hoort ook niet op de teampagina te staan.
     */
    private function ownResult(array $team, array $game): array
    {
        $sessions = RoomSession::where(['team_id' => $team['id'], 'game_id' => $game['id']]);

        return [
            'points' => array_sum(array_column($sessions, 'points')),
            'active_seconds' => (new GameTimerService)->totalActiveSecondsForTeam((int) $team['id'], (int) $game['id']),
            'rooms_played' => count(array_filter($sessions, fn ($s) => in_array($s['status'], RoomSession::PLAYED, true))),
        ];
    }
}
