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
        'allow_image_answer' => 'INTEGER NOT NULL DEFAULT 0',
    ],
    'room_sessions' => [
        'result_seen' => 'INTEGER NOT NULL DEFAULT 1',
    ],
    'answer_attempts' => [
        'image_path' => 'TEXT',
        'review_status' => "TEXT NOT NULL DEFAULT 'auto'",
        'reviewed_by' => 'INTEGER',
        'reviewed_at' => 'TEXT',
        'acknowledged' => 'INTEGER NOT NULL DEFAULT 1',
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

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    team_id INTEGER NOT NULL REFERENCES teams(id) ON DELETE CASCADE,
    sender TEXT NOT NULL,
    body TEXT NOT NULL,
    read_by_admin_at TEXT,
    read_by_team_at TEXT,
    created_at TEXT,
    updated_at TEXT
)
SQL);
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_chat_team ON chat_messages(team_id)');
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_chat_game ON chat_messages(game_id)');

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    created_at TEXT,
    updated_at TEXT
)
SQL);

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_aa_review_status ON answer_attempts(review_status)");

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
