<?php

/**
 * Zet de bestaande SQLite-database over naar de MySQL-database uit .env.
 *
 *   php database/sqlite-to-mysql.php [pad/naar/database.sqlite]
 *
 * De MySQL-tabellen worden opnieuw aangemaakt (bestaande inhoud gaat weg) en
 * daarna rij voor rij gevuld. Alleen nodig als je de speelgegevens uit de
 * SQLite-versie wilt bewaren; voor een nieuw spel is `php database/seed.php`
 * genoeg.
 */

require __DIR__.'/../src/autoload.php';

use App\Database;

if (! Database::isMysql()) {
    exit("DB_CONNECTION staat niet op mysql; zet dat eerst in .env.\n");
}

$sqlitePad = $argv[1] ?? __DIR__.'/../storage/database.sqlite';

if (! is_file($sqlitePad)) {
    exit("SQLite-bestand niet gevonden: $sqlitePad\n");
}

$oud = new PDO('sqlite:'.$sqlitePad);
$oud->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$oud->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$nieuw = Database::connection();

// Ouders voor kinderen, zodat de foreign keys kloppen.
$tabellen = ['users', 'games', 'teams', 'rooms', 'room_sessions', 'answer_attempts', 'chat_messages', 'settings', 'audit_logs'];

$nieuw->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (array_reverse($tabellen) as $tabel) {
    $nieuw->exec("DROP TABLE IF EXISTS $tabel");
}
$nieuw->exec('SET FOREIGN_KEY_CHECKS = 1');

Database::createSchema();

foreach ($tabellen as $tabel) {
    $bestaat = $oud->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = '$tabel'")->fetch();

    if ($bestaat === false) {
        echo "overgeslagen (bestaat niet in SQLite): $tabel\n";

        continue;
    }

    $rijen = $oud->query("SELECT * FROM $tabel")->fetchAll();

    if ($rijen === []) {
        echo "leeg: $tabel\n";

        continue;
    }

    $kolommen = array_keys($rijen[0]);
    $gequote = array_map(fn ($k) => '`'.$k.'`', $kolommen);
    $plaatshouders = array_map(fn ($k) => ':'.$k, $kolommen);

    $stmt = $nieuw->prepare(
        'INSERT INTO '.$tabel.' ('.implode(', ', $gequote).') VALUES ('.implode(', ', $plaatshouders).')'
    );

    foreach ($rijen as $rij) {
        // Lege datumstrings zijn in MySQL een fout, NULL is de bedoeling.
        foreach ($rij as $kolom => $waarde) {
            if ($waarde === '') {
                $rij[$kolom] = null;
            }
        }

        $stmt->execute($rij);
    }

    echo 'overgezet: '.$tabel.' ('.count($rijen)." rijen)\n";
}

echo "Klaar.\n";
