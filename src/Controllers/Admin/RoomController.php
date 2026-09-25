<?php

namespace App\Controllers\Admin;

use App\Csrf;
use App\Game;
use App\Html;
use App\Room;
use App\RoomSession;
use App\View;
use RuntimeException;

class RoomController
{
    private const MAX_IMAGE_MB = 4;

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

        Room::insert([
            'game_id' => $gameId,
            'name' => $data['name'],
            'description' => $data['description'],
            'route_instructions' => $data['route_instructions'],
            'instructions' => $data['instructions'],
            'answer' => $data['answer'],
            'alternative_answers' => json_encode($data['alternative_answers']),
            'exclusive' => $data['exclusive'],
            'active' => $data['active'],
            'allow_image_answer' => $data['allow_image_answer'],
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
        $room = Room::find((int) $roomId);

        Room::update((int) $roomId, [
            'name' => $data['name'],
            'description' => $data['description'],
            'route_instructions' => $data['route_instructions'],
            'instructions' => $data['instructions'],
            'answer' => $data['answer'],
            'alternative_answers' => json_encode($data['alternative_answers']),
            'exclusive' => $data['exclusive'],
            'active' => $data['active'],
            'allow_image_answer' => $data['allow_image_answer'],
        ]);

        // Afbeeldingen die uit de tekst gehaald zijn, hoeven niet te blijven staan.
        $this->ruimLosseAfbeeldingenOp(
            [$room['instructions'] ?? '', $room['route_instructions'] ?? ''],
            [$data['instructions'] ?? '', $data['route_instructions'] ?? '']
        );

        View::flash('Kamer bijgewerkt.');
        header("Location: /admin/games/$gameId/rooms");
    }

    public function destroy(string $gameId, string $roomId): void
    {
        $this->guardPost();
        $room = Room::find((int) $roomId);

        $this->wisAfbeelding($room['image_path'] ?? null);
        $this->ruimLosseAfbeeldingenOp([$room['instructions'] ?? '', $room['route_instructions'] ?? ''], []);

        Room::delete((int) $roomId);
        View::flash('Kamer verwijderd.');
        header("Location: /admin/games/$gameId/rooms");
    }

    /** Ontvangt een afbeelding uit de editor en geeft de URL terug. */
    public function uploadImage(string $gameId): void
    {
        header('Content-Type: application/json');

        if (! Csrf::verify()) {
            http_response_code(419);
            echo json_encode(['error' => 'De pagina is verlopen. Laad hem opnieuw en probeer het nog eens.']);

            return;
        }

        try {
            $naam = $this->bewaarAfbeelding($_FILES['image'] ?? null);
        } catch (RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()]);

            return;
        }

        if ($naam === null) {
            http_response_code(422);
            echo json_encode(['error' => 'Er is geen bestand ontvangen.']);

            return;
        }

        echo json_encode(['url' => '/storage/rooms/'.$naam]);
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
            // De editor levert HTML aan; die gaat er nooit ongefilterd in.
            'instructions' => Html::clean($_POST['instructions'] ?? null),
            'route_instructions' => Html::clean($_POST['route_instructions'] ?? null),
            'answer' => trim((string) ($_POST['answer'] ?? '')),
            'alternative_answers' => $alternatives,
            'exclusive' => ($_POST['exclusive'] ?? '0') === '1' ? 1 : 0,
            'active' => ($_POST['active'] ?? '0') === '1' ? 1 : 0,
            'allow_image_answer' => ($_POST['allow_image_answer'] ?? '0') === '1' ? 1 : 0,
        ];
    }

    /**
     * Slaat een geüploade afbeelding op onder een eigen, willekeurige naam.
     * Het bestandstype komt uit de inhoud (getimagesize), niet uit de naam of
     * het door de browser opgegeven type: die zijn allebei te vervalsen.
     */
    private function bewaarAfbeelding(?array $bestand): ?string
    {
        if ($bestand === null || ($bestand['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($bestand['error'] === UPLOAD_ERR_INI_SIZE || $bestand['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('De afbeelding is te groot (maximaal '.self::MAX_IMAGE_MB.' MB).');
        }

        if ($bestand['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($bestand['tmp_name'])) {
            throw new RuntimeException('Het uploaden van de afbeelding is mislukt.');
        }

        if ($bestand['size'] > self::MAX_IMAGE_MB * 1024 * 1024) {
            throw new RuntimeException('De afbeelding is te groot (maximaal '.self::MAX_IMAGE_MB.' MB).');
        }

        $soorten = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        $info = @getimagesize($bestand['tmp_name']);

        if ($info === false || ! isset($soorten[$info[2]])) {
            throw new RuntimeException('Alleen JPG, PNG, GIF of WebP kunnen gebruikt worden.');
        }

        $map = self::afbeeldingenMap();
        if (! is_dir($map) && ! @mkdir($map, 0775, true) && ! is_dir($map)) {
            throw new RuntimeException('De map voor afbeeldingen kon niet aangemaakt worden.');
        }

        $naam = bin2hex(random_bytes(16)).'.'.$soorten[$info[2]];

        if (! move_uploaded_file($bestand['tmp_name'], $map.'/'.$naam)) {
            throw new RuntimeException('De afbeelding kon niet opgeslagen worden.');
        }

        return $naam;
    }

    /** Verwijdert afbeeldingen die in de oude tekst stonden maar niet meer in de nieuwe. */
    private function ruimLosseAfbeeldingenOp(array $oud, array $nieuw): void
    {
        $verschil = array_diff($this->afbeeldingenIn($oud), $this->afbeeldingenIn($nieuw));

        foreach ($verschil as $naam) {
            $this->wisAfbeelding($naam);
        }
    }

    /** @return string[] bestandsnamen van /storage/rooms/... in de teksten */
    private function afbeeldingenIn(array $teksten): array
    {
        preg_match_all('#/storage/rooms/([A-Za-z0-9]+\.(?:jpg|png|gif|webp))#', implode(' ', $teksten), $treffers);

        return array_unique($treffers[1]);
    }

    private function wisAfbeelding(?string $naam): void
    {
        // basename() houdt paden als "../../.env" buiten de deur.
        if ($naam === null || $naam === '' || $naam !== basename($naam)) {
            return;
        }

        $pad = self::afbeeldingenMap().'/'.$naam;
        if (is_file($pad)) {
            @unlink($pad);
        }
    }

    private static function afbeeldingenMap(): string
    {
        return dirname(__DIR__, 3).'/storage/app/public/rooms';
    }

    private function guardPost(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }
    }
}
