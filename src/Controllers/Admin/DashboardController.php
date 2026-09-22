<?php

namespace App\Controllers\Admin;

use App\Auth;
use App\AuditLog;
use App\Csrf;
use App\Game;
use App\Room;
use App\RoomSession;
use App\Services\GameService;
use App\Services\GameTimerService;
use App\Services\LeaderboardService;
use App\Services\RoomAssignmentService;
use App\Team;
use App\View;

class DashboardController
{
    public function show(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        $game = (new GameService)->finalizeIfEnded($game);

        $tab = $_GET['tab'] ?? 'teams';
        if (! in_array($tab, ['teams', 'rooms', 'leaderboard'], true)) {
            $tab = 'teams';
        }

        $timer = new GameTimerService;

        $teams = View::sortByNatural(Team::where(['game_id' => $game['id']]), 'name');
        $teamsOverview = array_map(function ($team) use ($game, $timer) {
            $sessions = RoomSession::where(['team_id' => $team['id']]);
            $current = null;
            foreach ($sessions as $s) {
                if (in_array($s['status'], RoomSession::OCCUPYING, true) && ($current === null || $s['id'] > $current['id'])) {
                    $current = $s;
                }
            }

            return [
                'team' => $team,
                'current_session' => $current,
                'current_room' => $current ? Room::find((int) $current['room_id']) : null,
                'points' => array_sum(array_column($sessions, 'points')),
                'active_seconds' => $timer->totalActiveSecondsForTeam((int) $team['id'], (int) $game['id']),
                'rooms_played' => count(array_filter($sessions, fn ($s) => in_array($s['status'], RoomSession::PLAYED, true))),
            ];
        }, $teams);

        $rooms = View::sortByNatural(Room::where(['game_id' => $game['id']]), 'name');
        foreach ($rooms as &$room) {
            $room['active_teams_count'] = Room::activeTeamsCount((int) $room['id']);
        }
        unset($room);

        $standings = $tab === 'leaderboard' ? (new LeaderboardService)->standings($game) : [];

        $manualAssignTeamId = isset($_GET['assign_team']) ? (int) $_GET['assign_team'] : null;
        $allRoomsForAssign = $manualAssignTeamId
            ? View::sortByNatural(array_values(array_filter(Room::where(['game_id' => $game['id']]), fn ($r) => (int) $r['active'] === 1)), 'name')
            : [];

        $resettingSessionId = isset($_GET['reset_session']) ? (int) $_GET['reset_session'] : null;
        $adjustingScoreSessionId = isset($_GET['adjust_score']) ? (int) $_GET['adjust_score'] : null;
        $adjustingSession = $adjustingScoreSessionId ? RoomSession::find($adjustingScoreSessionId) : null;

        echo View::renderAdmin('admin/dashboard', [
            'game' => $game,
            'tab' => $tab,
            'teamsOverview' => $teamsOverview,
            'roomsOverview' => $rooms,
            'standings' => $standings,
            'remainingEventSeconds' => $timer->remainingEventSeconds($game),
            'manualAssignTeamId' => $manualAssignTeamId,
            'allRoomsForAssign' => $allRoomsForAssign,
            'resettingSessionId' => $resettingSessionId,
            'adjustingScoreSessionId' => $adjustingScoreSessionId,
            'adjustingSession' => $adjustingSession,
            'fingerprint' => $this->fingerprint($game),
        ], $game['name'], $game);
    }

    /** Zie PlayController::status(): het dashboard ververst alleen bij echte wijzigingen. */
    public function status(string $gameId): void
    {
        header('Content-Type: application/json');
        $game = Game::find((int) $gameId);
        $game = (new GameService)->finalizeIfEnded($game);

        echo json_encode(['fingerprint' => $this->fingerprint($game)]);
    }

    private function fingerprint(array $game): string
    {
        $parts = [$game['status'], (string) $game['end_time']];

        foreach (RoomSession::where(['game_id' => $game['id']]) as $s) {
            $parts[] = "s{$s['id']}:{$s['status']}:{$s['room_id']}:{$s['points']}";
        }
        foreach (Team::where(['game_id' => $game['id']]) as $t) {
            $parts[] = "t{$t['id']}:{$t['active']}";
        }
        foreach (Room::where(['game_id' => $game['id']]) as $r) {
            $parts[] = "r{$r['id']}:{$r['active']}";
        }

        return md5(implode('|', $parts));
    }

    public function assignRoom(string $gameId): void
    {
        $this->guardPost();
        $teamId = (int) ($_POST['team_id'] ?? 0);
        $roomId = (int) ($_POST['room_id'] ?? 0);

        $team = Team::first(['id' => $teamId, 'game_id' => $gameId]);
        $room = Room::first(['id' => $roomId, 'game_id' => $gameId]);

        if ($team && $room) {
            (new RoomAssignmentService)->assignSpecificRoom($team, $room);
            $admin = Auth::admin();
            AuditLog::log((int) $admin['id'], 'team.assign_room', Team::class, $teamId, [], ['room_id' => $roomId]);
        }

        header("Location: /admin/games/$gameId/dashboard");
    }

    public function resetSession(string $gameId): void
    {
        $this->guardPost();
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $session = RoomSession::first(['id' => $sessionId, 'game_id' => $gameId]);

        if ($session) {
            RoomSession::delete($sessionId);
            $admin = Auth::admin();
            AuditLog::log((int) $admin['id'], 'room_session.reset', RoomSession::class, $sessionId);
        }

        header("Location: /admin/games/$gameId/dashboard");
    }

    public function adjustScore(string $gameId): void
    {
        $this->guardPost();
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $value = max(0, min(3, (int) ($_POST['points'] ?? 0)));
        $session = RoomSession::first(['id' => $sessionId, 'game_id' => $gameId]);

        if ($session) {
            $old = ['points' => (int) $session['points']];
            RoomSession::update($sessionId, ['points' => $value]);
            $admin = Auth::admin();
            AuditLog::log((int) $admin['id'], 'room_session.adjust_score', RoomSession::class, $sessionId, $old, ['points' => $value]);
        }

        header("Location: /admin/games/$gameId/dashboard");
    }

    public function toggleTeamActive(string $gameId): void
    {
        $this->guardPost();
        $teamId = (int) ($_POST['team_id'] ?? 0);
        $team = Team::first(['id' => $teamId, 'game_id' => $gameId]);

        if ($team) {
            $newActive = (int) $team['active'] === 1 ? 0 : 1;
            Team::update($teamId, ['active' => $newActive]);
            $admin = Auth::admin();
            AuditLog::log((int) $admin['id'], 'team.toggle_active', Team::class, $teamId);
        }

        header("Location: /admin/games/$gameId/dashboard");
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }
}
