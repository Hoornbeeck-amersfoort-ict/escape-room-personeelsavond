<?php

require __DIR__.'/../src/autoload.php';

use App\Database;
use App\Game;
use App\Room;
use App\Team;
use App\User;

$dbPath = __DIR__.'/../storage/database.sqlite';

// Ook -wal en -shm weg: in WAL-modus staan de laatste wijzigingen in die
// bestanden, en een achtergebleven wal hoort niet bij een nieuwe database.
foreach ([$dbPath, $dbPath.'-wal', $dbPath.'-shm'] as $bestand) {
    if (is_file($bestand)) {
        unlink($bestand);
    }
}

touch($dbPath);

// De kamers uit de oude database bestaan niet meer, dus hun afbeeldingen ook niet.
foreach (glob(__DIR__.'/../storage/app/public/rooms/*') ?: [] as $oudeAfbeelding) {
    unlink($oudeAfbeelding);
}
Database::connection()->exec(file_get_contents(__DIR__.'/schema.sql'));

User::insert([
    'name' => 'Beheerder',
    'email' => 'admin@example.com',
    'password' => password_hash('password', PASSWORD_DEFAULT),
]);

$gameId = Game::insert(['name' => 'Personeelsavond Escape Room', 'status' => 'draft', 'start_time' => null, 'end_time' => null]);

$usedCodes = [];
for ($i = 1; $i <= 15; $i++) {
    do {
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    } while (isset($usedCodes[$code]));
    $usedCodes[$code] = true;

    Team::insert([
        'game_id' => $gameId,
        'name' => "Team $i",
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'code' => $code,
        'active' => 1,
    ]);

    echo "Team $i: $code\n";
}

$trivia = [
    ['Kamer 1', 'Aardrijkskunde', 'Wat is de hoofdstad van Nederland?', 'Amsterdam', []],
    ['Kamer 2', 'Wiskunde', 'Wat is de uitkomst van 12 x 12?', '144', []],
    ['Kamer 3', 'Geschiedenis', 'In welk jaar eindigde de Tweede Wereldoorlog?', '1945', []],
    ['Kamer 4', 'Natuur', 'Wat is het grootste zoogdier ter wereld?', 'blauwe vinvis', ['vinvis', 'blauwe walvis']],
    ['Kamer 5', 'Scheikunde', 'Wat is het chemisch symbool voor goud?', 'Au', []],
    ['Kamer 6', 'Sport', 'Hoeveel spelers staan er in een voetbalteam op het veld (exclusief wissels)?', '11', ['elf']],
    ['Kamer 7', 'Muziek', 'Hoeveel snaren heeft een standaard gitaar?', '6', ['zes']],
    ['Kamer 8', 'Film', 'Welk dier is de hoofdrolspeler in de film "Nemo"?', 'clownvis', ['vis', 'clownsvis']],
    ['Kamer 9', 'Aardrijkskunde', 'Wat is de langste rivier ter wereld?', 'Nijl', []],
    ['Kamer 10', 'Wiskunde', 'Hoeveel graden heeft een rechte hoek?', '90', []],
    ['Kamer 11', 'Techniek', 'Welk bedrijf ontwikkelde het besturingssysteem Windows?', 'Microsoft', []],
    ['Kamer 12', 'Biologie', 'Hoeveel benen heeft een spin?', '8', ['acht']],
    ['Kamer 13', 'Geschiedenis', 'Wie schilderde de Nachtwacht?', 'Rembrandt', ['Rembrandt van Rijn']],
    ['Kamer 14', 'Sport', 'Om de hoeveel jaar worden de Olympische Zomerspelen gehouden?', '4', ['vier']],
    ['Kamer 15', 'Aardrijkskunde', 'Wat is het kleinste land ter wereld?', 'Vaticaanstad', []],
    ['Kamer 16', 'Taal', 'Hoeveel letters heeft het Nederlandse alfabet?', '26', ['zesentwintig']],
    ['Kamer 17', 'Sterrenkunde', 'Welke planeet staat bekend als de rode planeet?', 'Mars', []],
];

foreach ($trivia as [$name, $description, $instructions, $answer, $alternatives]) {
    Room::insert([
        'game_id' => $gameId,
        'name' => $name,
        'description' => $description,
        'instructions' => $instructions,
        'answer' => $answer,
        'alternative_answers' => json_encode($alternatives),
        'active' => 1,
    ]);
}

echo "Seeded 1 game, 15 teams (willekeurige pincodes hierboven), 17 rooms.\n";
echo "Admin login: admin@example.com / password\n";
