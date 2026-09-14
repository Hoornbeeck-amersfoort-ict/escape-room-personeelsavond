<?php

namespace App\Controllers\Admin;

use App\Csrf;
use App\Game;
use App\Room;
use App\RoomSession;
use App\View;

class RoomController
{
    public function index(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        $rooms = View::sortByNatural(Room::where(['game_id' => $gameId]), 'name');
        foreach ($rooms as &$room) {
            $room['room_sessions_count'] = RoomSession::count(['room_id' => $room['id']]);
        }
        unset($room);

        echo View::renderAdmin('admin/rooms/index', ['game' => $game, 'rooms' => $rooms], 'Kamers — '.$game['name'], $game);
    }

    public function create(string $gameId): void
    {
        $game = Game::find((int) $gameId);
        echo View::renderAdmin('admin/rooms/create', ['game' => $game, 'room' => null], 'Nieuwe kamer', $game);
    }

    public function store(string $gameId): void
    {
        $this->guardPost();
        $data = $this->collectInput();

        if ($data['name'] === '' || $data['answer'] === '') {
            View::flash('Naam en antwoord zijn verplicht.');
            header("Location: /admin/games/$gameId/rooms/create");

            return;
        }

        $id = Room::insert([
            'game_id' => $gameId,
            'name' => $data['name'],
            'description' => $data['description'],
            'instructions' => $data['instructions'],
            'answer' => $data['answer'],
            'alternative_answers' => json_encode($data['alternative_answers']),
            'active' => $data['active'],
        ]);

        View::flash("Kamer \"{$data['name']}\" aangemaakt.");
        header("Location: /admin/games/$gameId/rooms");
    }

    public function edit(string $gameId, string $roomId): void
    {
        $game = Game::find((int) $gameId);
        $room = Room::find((int) $roomId);
        echo View::renderAdmin('admin/rooms/edit', ['game' => $game, 'room' => $room], 'Kamer bewerken', $game);
    }

    public function update(string $gameId, string $roomId): void
    {
        $this->guardPost();
        $data = $this->collectInput();

        Room::update((int) $roomId, [
            'name' => $data['name'],
            'description' => $data['description'],
            'instructions' => $data['instructions'],
            'answer' => $data['answer'],
            'alternative_answers' => json_encode($data['alternative_answers']),
            'active' => $data['active'],
        ]);

        View::flash('Kamer bijgewerkt.');
        header("Location: /admin/games/$gameId/rooms");
    }

    public function destroy(string $gameId, string $roomId): void
    {
        $this->guardPost();
        Room::delete((int) $roomId);
        View::flash('Kamer verwijderd.');
        header("Location: /admin/games/$gameId/rooms");
    }

    public function toggleActive(string $gameId, string $roomId): void
    {
        $this->guardPost();
        $room = Room::find((int) $roomId);
        $newActive = (int) $room['active'] === 1 ? 0 : 1;
        Room::update((int) $roomId, ['active' => $newActive]);

        View::flash($newActive ? 'Kamer geactiveerd.' : 'Kamer gedeactiveerd.');
        header("Location: /admin/games/$gameId/rooms");
    }

    private function collectInput(): array
    {
        $alternatives = array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', (string) ($_POST['alternative_answers'] ?? ''))
        ), fn ($line) => $line !== ''));

        return [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'instructions' => trim((string) ($_POST['instructions'] ?? '')) ?: null,
            'answer' => trim((string) ($_POST['answer'] ?? '')),
            'alternative_answers' => $alternatives,
            'active' => ($_POST['active'] ?? '0') === '1' ? 1 : 0,
        ];
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }
}
