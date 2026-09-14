<?php

use App\Csrf;
use App\View;

/** @var array $team */
/** @var array $game */
/** @var string $state */
/** @var array|null $feedback */
/** @var int $remainingEventSeconds */
/** @var array|null $session */
/** @var array|null $room */

function fmtDur(int $s): string
{
    return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
}
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Escape Room</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
<?php if ($state !== 'finished'): ?>
<script>setTimeout(() => window.location.reload(), 5000);</script>
<?php endif; ?>

<div class="team-header">
    <span style="font-weight:600;color:#cbd5e1;"><?= View::e($team['name']) ?></span>
    <?php if ($game['status'] === 'running'): ?>
        <div class="timer-pill" id="event-timer" data-remaining="<?= $remainingEventSeconds ?>">Nog <?= fmtDur($remainingEventSeconds) ?></div>
    <?php endif; ?>
    <form method="POST" action="/logout">
        <?= Csrf::field() ?>
        <button type="submit" class="secondary" style="background:none;color:#64748b;text-decoration:underline;font-weight:400;padding:0;">uitloggen</button>
    </form>
</div>

<main class="play-main">

<?php if ($state === 'not_started'): ?>
    <div class="text-center" style="margin-top:3rem;">
        <div class="big-emoji">⏳</div>
        <h1>Nog even geduld</h1>
        <p style="color:#94a3b8;">Het spel is nog niet gestart. Deze pagina ververst automatisch zodra het begint.</p>
    </div>

<?php elseif ($state === 'feedback'):
    $type = $feedback['type']; ?>
    <div class="text-center" style="margin-top:2rem;">
        <?php if ($type === 'correct'): ?>
            <div class="big-emoji">✅</div>
            <h1 style="color:#34d399;">GOED!</h1>
            <p>Kamer opgelost.</p>
            <p style="font-size:1.5rem;font-weight:800;color:#fbbf24;">+<?= (int) $feedback['points'] ?> punten</p>
        <?php elseif ($type === 'failed'): ?>
            <div class="big-emoji">❌</div>
            <h1 style="color:#f87171;">Helaas!</h1>
            <p>Geen pogingen meer over. Kamer verloren.</p>
            <p style="font-size:1.2rem;font-weight:700;color:#94a3b8;">+0 punten</p>
        <?php elseif ($type === 'given_up'): ?>
            <div class="big-emoji">🏳️</div>
            <h1>Kamer opgegeven</h1>
            <p style="font-size:1.2rem;font-weight:700;color:#94a3b8;">+0 punten</p>
        <?php else: ?>
            <div class="big-emoji">⚠️</div>
            <h1><?= View::e($feedback['message'] ?? 'Er ging iets mis.') ?></h1>
        <?php endif; ?>

        <?php if (in_array($type, ['correct', 'failed', 'given_up'], true)): ?>
            <div style="margin-top:2rem;">
                <?php if (! empty($feedback['next_room_name'])): ?>
                    <p style="color:#94a3b8;font-size:.9rem;">Ga nu naar:</p>
                    <p style="font-size:1.5rem;font-weight:800;"><?= View::e(mb_strtoupper($feedback['next_room_name'])) ?></p>
                <?php elseif (! empty($feedback['all_rooms_played'])): ?>
                    <p>Jullie hebben alle kamers gespeeld!</p>
                <?php elseif (! empty($feedback['waiting'])): ?>
                    <p>Er is nog geen nieuwe kamer beschikbaar. Blijf op deze pagina.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="/play" class="btn w-full" style="display:block;margin-top:2.5rem;">Verder</a>
    </div>

<?php elseif ($state === 'finished'): ?>
    <div class="text-center" style="margin-top:2rem;">
        <div class="big-emoji">🏁</div>
        <h1>Het evenement is afgelopen!</h1>
        <div class="room-box" style="text-align:left;">
            <p style="color:#94a3b8;font-size:.85rem;">Jullie score</p>
            <p style="font-size:1.75rem;font-weight:800;color:#fbbf24;"><?= (int) $ownResult['points'] ?> punten</p>
            <p style="color:#94a3b8;font-size:.85rem;margin-top:1rem;">Actieve speeltijd</p>
            <p style="font-size:1.2rem;font-weight:700;"><?= fmtDur($ownResult['active_seconds']) ?></p>
            <p style="color:#94a3b8;font-size:.85rem;margin-top:1rem;">Kamers gespeeld</p>
            <p style="font-size:1.2rem;font-weight:700;"><?= (int) $ownResult['rooms_played'] ?></p>
            <hr style="border-color:#334155;margin:1rem 0;">
            <p style="color:#94a3b8;font-size:.85rem;">Eindklassement</p>
            <p style="font-size:1.2rem;font-weight:700;">Plaats <?= (int) $ownResult['rank'] ?> van de <?= (int) $ownResult['total_teams'] ?></p>
        </div>
    </div>

<?php elseif ($state === 'all_played'): ?>
    <div class="text-center" style="margin-top:3rem;">
        <div class="big-emoji">🎉</div>
        <h1>Jullie hebben alle kamers gespeeld!</h1>
        <p style="color:#94a3b8;">Wacht op het einde van het evenement voor de uitslag.</p>
    </div>

<?php elseif ($state === 'waiting'): ?>
    <div class="text-center" style="margin-top:3rem;">
        <div class="big-emoji">🚪</div>
        <h1>Geen kamer beschikbaar</h1>
        <p style="color:#94a3b8;">Er is momenteel geen beschikbare kamer. Blijf op deze pagina, jullie krijgen automatisch een nieuwe kamer zodra er een vrijkomt.</p>
    </div>

<?php elseif ($state === 'assigned'): ?>
    <div class="text-center" style="margin-top:2rem;">
        <p style="text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-size:.85rem;">Jullie volgende kamer</p>
        <h1 style="font-size:2rem;"><?= View::e(mb_strtoupper($room['name'])) ?></h1>
        <p style="color:#94a3b8;">Ga naar <?= View::e($room['name']) ?>.</p>
        <form method="POST" action="/play/start" style="margin-top:2rem;">
            <?= Csrf::field() ?>
            <button type="submit" class="w-full" style="padding:1.2rem;font-size:1.1rem;">KAMER STARTEN</button>
        </form>
    </div>

<?php elseif ($state === 'active'): ?>
    <p style="text-align:center;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-size:.85rem;"><?= View::e(mb_strtoupper($room['name'])) ?></p>
    <h1 style="text-align:center;font-size:1.1rem;">OPDRACHT</h1>

    <div class="room-box">
        <?php if (! empty($room['description'])): ?><p><?= View::e($room['description']) ?></p><?php endif; ?>
        <?php if (! empty($room['instructions'])): ?><p style="white-space:pre-line;color:#cbd5e1;"><?= View::e($room['instructions']) ?></p><?php endif; ?>
    </div>

    <div class="attempts-row">
        <span>Pogingen: <?= (int) $attemptsUsed ?> / 3</span>
        <span id="active-timer" data-elapsed="<?= (int) $activeDurationSeconds ?>">Tijd: <?= fmtDur($activeDurationSeconds) ?></span>
    </div>

    <?php if ($feedback && $feedback['type'] === 'incorrect'): $remaining = (int) $feedback['attempts_remaining']; ?>
        <div class="error-box">
            Helaas, dit antwoord is niet goed.
            Nog <?= $remaining ?> <?= $remaining === 1 ? 'poging' : 'pogingen' ?>.
        </div>
    <?php endif; ?>

    <form method="POST" action="/play/answer">
        <?= Csrf::field() ?>
        <label for="answer">Antwoord:</label>
        <input id="answer" name="answer" type="text" autocomplete="off" autofocus required>
        <button type="submit" class="w-full">ANTWOORD CONTROLEREN</button>
    </form>

    <form method="POST" action="/play/give-up" onsubmit="return confirm('Weet je zeker dat je deze kamer wilt opgeven? Je krijgt 0 punten en kunt de kamer niet opnieuw spelen.');" style="margin-top:1.5rem;text-align:center;">
        <?= Csrf::field() ?>
        <button type="submit" style="background:none;color:#f87171;text-decoration:underline;font-weight:600;font-size:.9rem;">KAMER OPGEVEN</button>
    </form>
<?php endif; ?>

</main>

<script>
    // Purely cosmetic ticking clocks; the server remains the only source of truth.
    const eventTimer = document.getElementById('event-timer');
    if (eventTimer) {
        let remaining = parseInt(eventTimer.dataset.remaining, 10);
        setInterval(() => {
            if (remaining > 0) remaining--;
            const h = String(Math.floor(remaining / 3600)).padStart(2, '0');
            const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
            const s = String(remaining % 60).padStart(2, '0');
            eventTimer.textContent = `Nog ${h}:${m}:${s}`;
        }, 1000);
    }
    const activeTimer = document.getElementById('active-timer');
    if (activeTimer) {
        let elapsed = parseInt(activeTimer.dataset.elapsed, 10);
        setInterval(() => {
            elapsed++;
            const h = String(Math.floor(elapsed / 3600)).padStart(2, '0');
            const m = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
            const s = String(elapsed % 60).padStart(2, '0');
            activeTimer.textContent = `Tijd: ${h}:${m}:${s}`;
        }, 1000);
    }
</script>
</body>
</html>
