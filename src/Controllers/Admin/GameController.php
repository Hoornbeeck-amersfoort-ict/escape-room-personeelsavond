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
        $endTime = $this->parseDutchDatetime($_POST['end_time'] ?? null);

        Game::update((int) $gameId, ['name' => $name !== '' ? $name : $game['name'], 'end_time' => $endTime]);

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

    private function parseDutchDatetime(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('d-m-Y H:i', $value);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }
}
