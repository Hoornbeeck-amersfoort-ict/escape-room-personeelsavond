<?php

namespace App\Controllers\Admin;

use App\Csrf;
use App\Database;
use App\Game;
use App\Room;
use App\Services\GameService;
use App\Team;
use App\View;

class GameController
{
    public function index(): void
    {
        $games = Game::all('id DESC');
        foreach ($games as &$game) {
            $game['teams_count'] = Team::count(['game_id' => $game['id']]);
            $game['rooms_count'] = Room::count(['game_id' => $game['id']]);
        }
        unset($game);

        echo View::renderAdmin('admin/games/index', ['games' => $games], 'Games');
    }

    public function create(): void
    {
        echo View::renderAdmin('admin/games/create', [], 'Nieuw game');
    }

    public function store(): void
    {
        $this->guardPost();
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '') {
            View::flash('Naam is verplicht.');
            header('Location: /admin/games/create');

            return;
        }

        $id = Game::insert(['name' => $name, 'status' => 'draft', 'start_time' => null, 'end_time' => null]);
        View::flash('Game aangemaakt.');
        header("Location: /admin/games/$id/edit");
    }

    public function edit(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        echo View::renderAdmin('admin/games/edit', ['game' => $game], $game['name'], $game);
    }

    public function update(string $gameId): void
    {
        $this->guardPost();
        $game = Game::find((int) $gameId);

        $name = trim((string) ($_POST['name'] ?? ''));
        $startTime = $this->parseDatetimeLocal($_POST['start_time'] ?? null);
        $endTime = $this->parseDatetimeLocal($_POST['end_time'] ?? null);

        Game::update((int) $gameId, ['name' => $name !== '' ? $name : $game['name'], 'start_time' => $startTime, 'end_time' => $endTime]);

        $game = Game::find((int) $gameId);
        (new GameService)->finalizeIfEnded($game);

        View::flash('Game bijgewerkt.');
        header("Location: /admin/games/$gameId/edit");
    }

    public function destroy(string $gameId): void
    {
        $this->guardPost();
        Game::delete((int) $gameId);
        View::flash('Game verwijderd.');
        header('Location: /admin/games');
    }

    public function start(string $gameId): void
    {
        $this->guardPost();
        $game = Game::find((int) $gameId);

        if ($game['end_time'] === null) {
            View::flash('Stel eerst een eindtijd in voordat je het spel start.');
            header("Location: /admin/games/$gameId/edit");

            return;
        }

        (new GameService)->start($game);
        View::flash('Game gestart.');
        header("Location: /admin/games/$gameId/dashboard");
    }

    public function finish(string $gameId): void
    {
        $this->guardPost();
        $game = Game::find((int) $gameId);
        (new GameService)->finish($game);
        View::flash('Game beëindigd.');
        header("Location: /admin/games/$gameId/edit");
    }

    public function reset(string $gameId): void
    {
        $this->guardPost();
        $game = Game::find((int) $gameId);
        (new GameService)->reset($game);
        View::flash('Game gereset. Alle voortgang is gewist.');
        header("Location: /admin/games/$gameId/edit");
    }

    private function parseDatetimeLocal(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // <input type="datetime-local"> gives "2026-09-14T10:00".
        return str_replace('T', ' ', $value).':00';
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }
}
