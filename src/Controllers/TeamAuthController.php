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

        // Een draaiend spel wint altijd: anders zou een later aangemaakt (concept)
        // spel de teams van het lopende evenement van het inlogscherm duwen.
        // Is er niets gestart, dan het nieuwste spel, zodat vooraf testen kan.
        $games = Game::all('id DESC');
        $running = array_values(array_filter($games, fn ($g) => $g['status'] === 'running'));
        $game = $running[0] ?? $games[0] ?? null;
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

        if (Team::seatTakenByOther($team, session_id())) {
            View::flash('Er is al iemand van dit team ingelogd. Er kan maar één teamleider tegelijk spelen.');
            header('Location: /');

            return;
        }

        Auth::loginTeam($team);
        // Pas na het inloggen: session_regenerate_id() geeft een nieuw sessie-id.
        Team::claimSeat((int) $team['id'], session_id());
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
