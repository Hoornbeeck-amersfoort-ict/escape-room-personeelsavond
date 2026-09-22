<?php

/**
 * Voegt ontbrekende kolommen toe aan een bestaande database, zonder data te
 * wissen. Veilig om meermaals te draaien: bestaande kolommen worden overgeslagen.
 */

require __DIR__.'/../src/autoload.php';

use App\Database;

$nieuweKolommen = [
    'teams' => [
        'session_id' => 'TEXT',
        'session_seen_at' => 'TEXT',
    ],
    'rooms' => [
        'image_path' => 'TEXT',
        'exclusive' => 'INTEGER NOT NULL DEFAULT 0',
        'route_instructions' => 'TEXT',
    ],
];

$pdo = Database::connection();
$toegevoegd = 0;

foreach ($nieuweKolommen as $tabel => $kolommen) {
    $bestaand = array_column($pdo->query("PRAGMA table_info($tabel)")->fetchAll(), 'name');

    foreach ($kolommen as $kolom => $type) {
        if (in_array($kolom, $bestaand, true)) {
            continue;
        }

        $pdo->exec("ALTER TABLE $tabel ADD COLUMN $kolom $type");
        echo "toegevoegd: $tabel.$kolom\n";
        $toegevoegd++;
    }
}

// De losse afbeelding per kamer gaat op in de opdracht zelf: het formulier
// heeft nu één editor waarin tekst en afbeeldingen door elkaar staan.
$metAfbeelding = $pdo->query("SELECT id, instructions, image_path FROM rooms WHERE image_path IS NOT NULL AND image_path != ''")->fetchAll();
$verplaatst = 0;

foreach ($metAfbeelding as $kamer) {
    $inhoud = trim((string) $kamer['instructions']);

    if ($inhoud !== '' && ! str_contains($inhoud, '<')) {
        $inhoud = '<p>'.nl2br(htmlspecialchars($inhoud, ENT_QUOTES)).'</p>';
    }

    $inhoud .= '<p><img src="/storage/rooms/'.$kamer['image_path'].'" alt=""></p>';

    $stmt = $pdo->prepare('UPDATE rooms SET instructions = ?, image_path = NULL WHERE id = ?');
    $stmt->execute([$inhoud, $kamer['id']]);
    $verplaatst++;
}

if ($verplaatst > 0) {
    echo "afbeelding in de opdracht gezet voor $verplaatst kamer(s)\n";
}

echo $toegevoegd === 0 && $verplaatst === 0 ? "Database was al bijgewerkt.\n" : "Klaar.\n";
