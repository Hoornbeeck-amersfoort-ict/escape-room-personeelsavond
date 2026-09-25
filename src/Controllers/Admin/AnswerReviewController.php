<?php

namespace App\Controllers\Admin;

use App\AnswerAttempt;
use App\Auth;
use App\AuditLog;
use App\Csrf;
use App\Game;
use App\Room;
use App\RoomSession;
use App\Services\RoomSessionActions;
use App\Team;
use App\View;

class AnswerReviewController
{
    public function index(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        $attempts = AnswerAttempt::pendingReview((int) $gameId);

        $rows = array_map(function ($attempt) {
            $session = RoomSession::find((int) $attempt['room_session_id']);

            return [
                'attempt' => $attempt,
                'session' => $session,
                'room' => $session ? Room::find((int) $session['room_id']) : null,
                'team' => $session ? Team::find((int) $session['team_id']) : null,
            ];
        }, $attempts);

        echo View::renderAdmin('admin/answers/index', [
            'game' => $game,
            'rows' => $rows,
            'fingerprint' => $this->fingerprint($game),
        ], 'Antwoorden beoordelen — '.$game['name'], $game);
    }

    public function status(string $gameId): void
    {
        header('Content-Type: application/json');
        $game = Game::find((int) $gameId);
        echo json_encode(['fingerprint' => $this->fingerprint($game)]);
    }

    public function approve(string $gameId, string $attemptId): void
    {
        $this->review($gameId, $attemptId, true);
    }

    public function reject(string $gameId, string $attemptId): void
    {
        $this->review($gameId, $attemptId, false);
    }

    private function review(string $gameId, string $attemptId, bool $approve): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }

        $attempt = AnswerAttempt::find((int) $attemptId);
        $session = $attempt ? RoomSession::find((int) $attempt['room_session_id']) : null;

        if ($attempt && $session && (int) $session['game_id'] === (int) $gameId && $attempt['review_status'] === 'pending') {
            $admin = Auth::admin();
            (new RoomSessionActions)->reviewImageAnswer($attempt, (int) $admin['id'], $approve);
            AuditLog::log((int) $admin['id'], $approve ? 'answer.approve' : 'answer.reject', AnswerAttempt::class, (int) $attempt['id']);
        }

        header('Location: '.$this->safeRedirect($gameId, "/admin/games/$gameId/answers"));
    }

    /** Staat toe dat deze actie ook vanaf het overzicht (dashboard) uitgevoerd wordt en daarnaar terugkeert. */
    private function safeRedirect(string $gameId, string $default): string
    {
        $redirect = (string) ($_POST['redirect'] ?? '');

        return str_starts_with($redirect, "/admin/games/$gameId/") ? $redirect : $default;
    }

    private function fingerprint(array $game): string
    {
        $parts = [];
        foreach (AnswerAttempt::pendingReview((int) $game['id']) as $a) {
            $parts[] = $a['id'];
        }

        return md5(implode(',', $parts));
    }
}
