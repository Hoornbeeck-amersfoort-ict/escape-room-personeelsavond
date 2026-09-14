<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Game;
use App\Team;
use App\View;

class TeamAuthController
{
    public function showLogin(): void
    {
        if (Auth::team()) {
            header('Location: /play');

            return;
        }

        // Teams from the most recently created game are offered on the login screen,
        // draft or running — same as the Laravel version, so testing before a game
        // starts is possible.
        $games = Game::all('id DESC');
        $game = $games[0] ?? null;
        $teams = $game ? View::sortByNatural(Team::where(['game_id' => $game['id'], 'active' => 1]), 'name') : [];

        echo View::render('team/login', ['teams' => $teams]);
    }

    public function login(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            echo 'Page Expired';

            return;
        }

        $teamId = (int) ($_POST['team_id'] ?? 0);
        $code = trim((string) ($_POST['code'] ?? ''));

        $team = $teamId ? Team::find($teamId) : null;

        if (! $team || (int) $team['active'] !== 1 || ! Team::verifyCode($team, $code)) {
            View::flash('Onjuiste teamnaam of code.');
            header('Location: /');

            return;
        }

        Auth::loginTeam($team);
        header('Location: /play');
    }

    public function logout(): void
    {
        if (Csrf::verify()) {
            Auth::logoutTeam();
        }

        header('Location: /');
    }
}
