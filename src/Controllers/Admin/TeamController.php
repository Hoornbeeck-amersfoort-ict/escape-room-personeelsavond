<?php

namespace App\Controllers\Admin;

use App\Auth;
use App\AuditLog;
use App\Csrf;
use App\Game;
use App\RoomSession;
use App\Team;
use App\View;

class TeamController
{
    public function index(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        $teams = View::sortByNatural(Team::where(['game_id' => $gameId]), 'name');
        foreach ($teams as &$team) {
            $team['room_sessions_count'] = RoomSession::count(['team_id' => $team['id']]);
        }
        unset($team);

        echo View::renderAdmin('admin/teams/index', ['game' => $game, 'teams' => $teams], 'Teams — '.$game['name'], $game);
    }

    public function create(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        echo View::renderAdmin('admin/teams/create', ['game' => $game], 'Nieuw team', $game);
    }

    public function store(string $gameId): void
    {
        $this->guardPost();
        $name = trim((string) ($_POST['name'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));

        if ($name === '' || ! preg_match('/^\d{4}$/', $code)) {
            View::flash('Vul een teamnaam en een 4-cijferige code in.');
            header("Location: /admin/games/$gameId/teams/create");

            return;
        }

        $id = Team::insert([
            'game_id' => $gameId,
            'name' => $name,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'code' => $code,
            'active' => 1,
        ]);

        View::flash("Team \"$name\" aangemaakt met code $code.");
        header("Location: /admin/games/$gameId/teams");
    }

    public function edit(string $gameId, string $teamId): void
    {
        $game = Game::find((int) $gameId);
        $team = Team::find((int) $teamId);
        echo View::renderAdmin('admin/teams/edit', ['game' => $game, 'team' => $team], 'Team bewerken', $game);
    }

    public function update(string $gameId, string $teamId): void
    {
        $this->guardPost();
        $name = trim((string) ($_POST['name'] ?? ''));
        $active = isset($_POST['active']) && $_POST['active'] === '1' ? 1 : 0;

        Team::update((int) $teamId, ['name' => $name, 'active' => $active]);

        View::flash('Team bijgewerkt.');
        header("Location: /admin/games/$gameId/teams");
    }

    public function destroy(string $gameId, string $teamId): void
    {
        $this->guardPost();
        Team::delete((int) $teamId);
        View::flash('Team verwijderd.');
        header("Location: /admin/games/$gameId/teams");
    }

    public function resetCode(string $gameId, string $teamId): void
    {
        $this->guardPost();
        $team = Team::find((int) $teamId);
        $newCode = (string) random_int(1000, 9999);
        Team::setCode((int) $teamId, $newCode);

        $admin = Auth::admin();
        AuditLog::log((int) $admin['id'], 'team.reset_code', Team::class, (int) $teamId);

        View::flash("Nieuwe teamcode voor \"{$team['name']}\": {$newCode}");
        header("Location: /admin/games/$gameId/teams");
    }

    public function toggleActive(string $gameId, string $teamId): void
    {
        $this->guardPost();
        $team = Team::find((int) $teamId);
        $newActive = (int) $team['active'] === 1 ? 0 : 1;
        Team::update((int) $teamId, ['active' => $newActive]);

        $admin = Auth::admin();
        AuditLog::log((int) $admin['id'], 'team.toggle_active', Team::class, (int) $teamId);

        View::flash($newActive ? 'Team geactiveerd.' : 'Team geblokkeerd.');
        header("Location: /admin/games/$gameId/teams");
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }
}
