<?php

/**
 * Zet de kamers uit "Escapetour personeelsavond.docx" in de database.
 *
 * De afbeeldingen uit dat document staan in storage/app/import/; dit script
 * kopieert ze naar de openbare map en zet ze in de opdrachten. Opnieuw draaien
 * mag: bestaande kamers met dezelfde naam worden bijgewerkt, niet gedupliceerd.
 *
 * De oplossingen en toelichtingen uit het document zijn bewust weggelaten —
 * die horen bij de organisatie, niet op het scherm van de teams.
 */

require __DIR__.'/../src/autoload.php';

use App\Game;
use App\Html;
use App\Room;

$game = Game::all('id DESC')[0] ?? null;

if ($game === null) {
    exit("Geen game gevonden. Draai eerst php database/seed.php\n");
}

$gameId = (int) $game['id'];
$bron = __DIR__.'/../storage/app/import';
$doel = __DIR__.'/../storage/app/public/rooms';

if (! is_dir($doel)) {
    mkdir($doel, 0775, true);
}

/** Kopieert een afbeelding uit het document naar de openbare map. */
$afbeelding = function (string $bestand) use ($bron, $doel): string {
    $pad = "$bron/$bestand";

    if (! is_file($pad)) {
        exit("Afbeelding ontbreekt: $pad\n");
    }

    $soorten = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    $info = getimagesize($pad);
    $naam = substr(hash('sha256', $bestand.filesize($pad)), 0, 32).'.'.$soorten[$info[2]];

    if (! is_file("$doel/$naam")) {
        copy($pad, "$doel/$naam");
    }

    return '/storage/rooms/'.$naam;
};

$rebus = '<table><tr>'
    .'<td style="width: 33%"><img src="'.$afbeelding('image1.png').'" alt="" style="width: 100%"></td>'
    .'<td style="width: 33%"><img src="'.$afbeelding('image2.jpeg').'" alt="" style="width: 100%"></td>'
    .'<td style="width: 33%"><img src="'.$afbeelding('image3.jpeg').'" alt="" style="width: 100%"></td>'
    .'</tr><tr>'
    .'<td>− p</td><td>− oe</td><td>De codenaam van deze spion − 0</td>'
    .'</tr></table>';

$kamers = [
    [
        'name' => 'Room A',
        'description' => 'Gemaakt door Christian',
        'route_instructions' =>
            '<p>Wist jij dat Hoornbeeck Amersfoort twee cellen heeft? Als je de sectoren langsgaat kan je '
            .'natuurlijk al wel bedenken welk team deze heeft. Ga naar het lokaal waar die cellen zijn. '
            .'Denk je dat je de cellen bij elektrotechniek kan vinden? Jammer, zij doen andere spannende dingen. 😊</p>'
            .'<p>Als je de rebus oplost weet je het lokaal. Weet je het al? Loop er dan gelijk naartoe.</p>'
            .$rebus,
        // Bewust alleen de foto: daarop staan de zwarte vakjes (startpunt en
        // route), en die gaan verloren als je de letters gewoon overtypt.
        'instructions' =>
            '<p>Welke drie cijfers kunnen jullie halen uit de gouden tabel met behulp van het getallenkompas? '
            .'De code geeft toegang tot de cel waar je de code vindt voor de volgende room.</p>'
            .'<p><img src="'.$afbeelding('image4.png').'" alt="De gouden tabel en het getallenkompas" '
            .'style="float: none; display: block; width: 100%; margin: 1rem 0;"></p>',
        'answer' => '500',
        'alternative_answers' => [],
        'active' => 1,
    ],
    [
        'name' => 'Room B',
        'description' => 'Gemaakt door Dick',
        'route_instructions' => '<p>Deze room bevindt zich in het bouwkunde praktijklokaal, <strong>D002</strong>.</p>',
        'instructions' =>
            '<p>Er is een belangrijk bouwdossier kwijtgeraakt. Er hangt een nummer aan het dossier, en dat nummer '
            .'zoeken we. Het dossier bevat allerlei informatie die nodig is om het Techcentrum zo te verbouwen dat '
            .'team Mobiliteit zijn intrek kan nemen. Het dossier moet terug! Vind de code.</p>',
        'answer' => 'NOG-INVULLEN',
        'alternative_answers' => [],
        'active' => 0, // stond nog geen antwoord in het document
    ],
    [
        'name' => 'Room C',
        'description' => 'Gemaakt door Marit',
        'route_instructions' =>
            '<p>In ons schoolgebouw bevindt zich ook een bouwdeel C, waar onder andere de kapel te vinden is. '
            .'Ga daarheen.</p>',
        'instructions' =>
            '<p>Deze kapel werd in 1914 in gebruik genomen door het jongensinternaat st. Louis. De leerlingen waren '
            .'verdeeld over verschillende groepen. Elke groep schonk een glas-in-loodraam aan het klooster en koos '
            .'daarbij zelf een heilige die op het raam werd afgebeeld. Onderaan ieder raam staat vermeld welke klas '
            .'het heeft geschonken en welke heilige erop is afgebeeld.</p>'
            .'<p>Gebruik het klasnummer om een letter uit de naam van de heilige te selecteren. Let daarbij goed op: '
            .'gebruik alleen de roepnaam van de heilige, dus geen toevoegingen zoals st. of evt. voorletters. '
            .'Zoek de juiste heiligen aan de hand van de onderstaande kenmerken:</p>'
            .'<ol>'
            .'<li>De eerste heilige kreeg volgens de legende een bijzondere sleutel van Petrus, deze sleutel staat '
            .'bekend als de Sint-Servaassleutel. Petrus staat afgebeeld met dezelfde sleutel, als verwijzing naar '
            .'‘de sleutels van de hemel’.</li>'
            .'<li>De tweede heilige was priester van de Dominicanen. Hij heeft een halo rond zijn hoofd als teken van '
            .'heiligheid en een lelie in zijn hand als symbool voor zuiverheid.</li>'
            .'<li>De derde heilige was Bisschop. Zijn beroemde uitspraak ‘’Ons hart is rusteloos totdat het rust '
            .'vindt in U’’ wordt op het raam verbeeld door het hart dat hij in zijn hand houdt.</li>'
            .'<li>De laatste heilige die jullie zoeken staat afgebeeld naast de eerste christelijke martelaar.</li>'
            .'</ol>'
            .'<p>Als je deze letters achter elkaar zet krijg je een Latijns woord. Vul de Nederlandse vertaling in '
            .'om de code te kraken.</p>',
        'answer' => 'Leven',
        'alternative_answers' => ['het leven'],
        'active' => 1,
    ],
    [
        'name' => 'Room D',
        'description' => 'Gemaakt door Marit',
        'route_instructions' => '<p>Ga naar het B-plein.</p>',
        'instructions' =>
            '<p>Daar staat de deelauto, het kenteken hebben jullie nodig om deze code te kraken. Dit is namelijk meer '
            .'dan een kenteken. De letters vormen samen met de cijfers een verborgen code. Een oude regel zegt: '
            .'‘’Wie de plaats kent van elke letter, kan het geheim onthullen’’.</p>'
            .'<p>Ontdek welke plaats de letters in het alfabet innemen. Tel deze waarden samen met het getal uit het '
            .'kenteken. Wanneer alle getallen correct zijn samengebracht, kunnen jullie door naar de volgende room.</p>',
        'answer' => '80',
        'alternative_answers' => [],
        'active' => 1,
    ],
    [
        'name' => 'Room E',
        'description' => 'Gemaakt door Raymond',
        'route_instructions' => '<p>Ga naar de conciërgeloge.</p>',
        'instructions' =>
            '<p>Ik beweeg, maar verplaats mij niet. Ik keer steeds terug naar waar ik begon. Twee lijnen trekken hun '
            .'eigen weg rond één vast middelpunt. Op een bepaald moment wijzen ze samen iets aan.</p>'
            .'<p><strong>Vind dat moment.</strong></p>'
            .'<p>Je hebt nu twee getallen. Deel één van de twee door 5 en vermenigvuldig de uitkomst met het andere '
            .'getal. Wat verschijnt is geen antwoord, maar een nummer.</p>'
            .'<p>Heb je het juiste nummer? Ga naar de garderobe en zoek het bijbehorende kluisje. Daarin ligt een '
            .'nieuw raadsel met de code voor de volgende stap.</p>',
        'answer' => 'Hoevelaken',
        'alternative_answers' => [],
        'active' => 1,
    ],
    [
        'name' => 'Room F',
        'description' => 'Gemaakt door Marit',
        'route_instructions' => '<p>Ga naar de receptie.</p>',
        'instructions' =>
            '<p>Niet alle hints liggen verborgen in het gebouw, soms moet je gewoon de juiste persoon bellen. '
            .'Zoek het algemene telefoonnummer van deze locatie en luister aandachtig naar de boodschap die op '
            .'jullie wacht.</p>',
        'answer' => '21111914',
        'alternative_answers' => ['21-11-1914', '21 11 1914'],
        'active' => 1,
    ],
    [
        'name' => 'Room G',
        'description' => 'Gemaakt door Dick',
        'route_instructions' =>
            '<p>Grond, Weg en Waterbouw heeft ook een eigen lokaal: <strong>D102</strong>. Ga naar het lokaal.</p>',
        'instructions' => '<p>Maak de opdracht die in het lokaal klaarligt.</p>',
        'answer' => 'NOG-INVULLEN',
        'alternative_answers' => [],
        'active' => 0, // stond nog geen antwoord in het document
    ],
    [
        'name' => 'Room K',
        'description' => 'Gemaakt door Christian',
        'route_instructions' => null,
        'instructions' =>
            '<p>Room K doet denken aan vak K, in de Tweede Kamer. Daarom gaat deze opdracht over de Tweede Kamer.</p>'
            .'<p>In vak K (de ‘kabinetsbank’) zitten de ministers en staatssecretarissen. We gaan wat rekenen om de '
            .'code te bemachtigen.</p>'
            .'<p><img src="'.$afbeelding('image6.png').'" alt="Zo zitten de Kamerleden straks" '
            .'style="float: none; display: block; width: 100%; margin: 1rem 0;"></p>'
            .'<ol>'
            .'<li>Zet de letters van de partijen om in nummers (A=1, B=2, etc.) en tel dit bij elkaar.</li>'
            .'<li>Vermenigvuldig dit getal door het aantal zetels dat de kabinetspartijen hebben.</li>'
            .'<li>Trek hier 150 vanaf.</li>'
            .'<li>We willen in het kader van Artikel 1 van de Grondwet D66 niet achterstellen, daarom delen we de '
            .'uitkomst door 66 en ronden we af op twee decimalen achter de komma.</li>'
            .'<li>Deel dit getal door het aantal partijen in de Tweede Kamer. Rond dit getal af op twee decimalen '
            .'achter de komma.</li>'
            .'</ol>'
            .'<p>De uitkomst is de code voor de volgende room.</p>',
        'answer' => '3,40',
        'alternative_answers' => ['3.40'],
        'active' => 1,
    ],
];

$nieuw = 0;
$bijgewerkt = 0;

foreach ($kamers as $kamer) {
    $velden = [
        'game_id' => $gameId,
        'name' => $kamer['name'],
        'description' => $kamer['description'],
        'route_instructions' => Html::clean($kamer['route_instructions']),
        'instructions' => Html::clean($kamer['instructions']),
        'answer' => $kamer['answer'],
        'alternative_answers' => json_encode($kamer['alternative_answers']),
        'exclusive' => 0,
        'active' => $kamer['active'],
    ];

    $bestaand = Room::first(['game_id' => $gameId, 'name' => $kamer['name']]);

    if ($bestaand) {
        unset($velden['game_id'], $velden['name']);
        Room::update((int) $bestaand['id'], $velden);
        $bijgewerkt++;
    } else {
        Room::insert($velden);
        $nieuw++;
    }

    $status = $kamer['active'] ? 'actief' : 'inactief (nog geen antwoord)';
    echo "  {$kamer['name']}: $status\n";
}

// De oefenkamers uit seed.php staan het echte spel in de weg: uitzetten, niet
// weggooien, zodat ze er nog zijn als je toch wilt testen.
$uitgezet = 0;
foreach (Room::where(['game_id' => $gameId]) as $kamer) {
    if (str_starts_with($kamer['name'], 'Kamer ') && (int) $kamer['active'] === 1) {
        Room::update((int) $kamer['id'], ['active' => 0]);
        $uitgezet++;
    }
}

echo "\n$nieuw kamer(s) toegevoegd, $bijgewerkt bijgewerkt, $uitgezet oefenkamer(s) op inactief gezet.\n";
