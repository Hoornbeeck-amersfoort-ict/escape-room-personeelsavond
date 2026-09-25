<?php

namespace App\Controllers\Admin;

use App\ChatMessage;
use App\Csrf;
use App\Game;
use App\Team;
use App\View;

class ChatController
{
    public function index(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        $teams = View::sortByNatural(Team::where(['game_id' => $gameId]), 'name');

        $rows = array_map(function ($team) {
            $messages = ChatMessage::forTeam((int) $team['id']);

            return [
                'team' => $team,
                'unread' => ChatMessage::unreadByAdminCountForTeam((int) $team['id']),
                'last' => end($messages) ?: null,
            ];
        }, $teams);

        usort($rows, fn ($a, $b) => $b['unread'] <=> $a['unread']);

        echo View::renderAdmin('admin/chat/index', [
            'game' => $game,
            'rows' => $rows,
            'fingerprint' => $this->fingerprint($game),
        ], 'Chat — '.$game['name'], $game);
    }

    public function status(string $gameId): void
    {
        header('Content-Type: application/json');
        $game = Game::find((int) $gameId);
        echo json_encode(['fingerprint' => $this->fingerprint($game)]);
    }

    public function show(string $gameId, string $teamId): void
    {
        $game = Game::find((int) $gameId);
        $team = Team::first(['id' => $teamId, 'game_id' => $gameId]);

        if ($team === null) {
            header("Location: /admin/games/$gameId/chat");

            return;
        }

        ChatMessage::markReadByAdmin((int) $team['id']);

        echo View::renderAdmin('admin/chat/show', [
            'game' => $game,
            'team' => $team,
            'messages' => ChatMessage::forTeam((int) $team['id']),
            'fingerprint' => $this->threadFingerprint((int) $team['id']),
        ], 'Chat met '.$team['name'], $game);
    }

    public function threadStatus(string $gameId, string $teamId): void
    {
        header('Content-Type: application/json');
        echo json_encode(['fingerprint' => $this->threadFingerprint((int) $teamId)]);
    }

    public function send(string $gameId, string $teamId): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }

        $team = Team::first(['id' => $teamId, 'game_id' => $gameId]);
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($team && $body !== '' && mb_strlen($body) <= 2000) {
            ChatMessage::insert([
                'game_id' => $gameId,
                'team_id' => $team['id'],
                'sender' => 'admin',
                'body' => $body,
            ]);
            ChatMessage::markReadByAdmin((int) $team['id']);
        }

        header("Location: /admin/games/$gameId/chat/$teamId");
    }

    private function fingerprint(array $game): string
    {
        $parts = [];
        foreach (ChatMessage::where(['game_id' => $game['id']]) as $m) {
            $parts[] = $m['id'].':'.($m['read_by_admin_at'] !== null ? '1' : '0');
        }

        return md5(implode(',', $parts));
    }

    private function threadFingerprint(int $teamId): string
    {
        $parts = array_map(fn ($m) => $m['id'], ChatMessage::forTeam($teamId));

        return md5(implode(',', $parts));
    }
}
