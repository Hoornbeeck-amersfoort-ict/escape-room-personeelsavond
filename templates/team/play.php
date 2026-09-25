<?php

use App\Csrf;
use App\Html;
use App\View;

/** @var array $team */
/** @var array $game */
/** @var string $state */
/** @var array|null $feedback */
/** @var int $remainingEventSeconds */
/** @var array|null $session */
/** @var array|null $room */
/** @var string $fingerprint */
/** @var string|null $supportPhone */
/** @var string|null $supportNote */
/** @var bool $chatEnabled */
/** @var array $chatMessages */
/** @var string|null $pendingImagePath */

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
    <link rel="stylesheet" href="<?= asset('/style.css') ?>">
</head>

<body>
    <div class="team-header">
        <span style="font-weight:600;color:#cbd5e1;"><?= View::e($team['name']) ?></span>
        <?php if ($game['status'] === 'running'): ?>
            <div class="timer-pill" id="event-timer" data-remaining="<?= $remainingEventSeconds ?>">Nog
                <?= fmtDur($remainingEventSeconds) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="/logout">
            <?= Csrf::field() ?>
            <button type="submit" class="secondary"
                style="background:none;color:#cbd5e1;text-decoration:underline;font-weight:400;padding:0;">uitloggen</button>
        </form>
    </div>

    <main class="play-main">

        <?php if ($state === 'not_started'): ?>
            <div class="text-center" style="margin-top:3rem;">
                <div class="big-emoji">⏳</div>
                <h1>Nog even geduld</h1>
                <p style="color:#cbd5e1;">Het spel is nog niet gestart.</p>
                <a href="/play" class="btn w-full" style="display:block;margin-top:2rem;">Vernieuwen</a>
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
                    <p style="font-size:1.2rem;font-weight:700;color:#cbd5e1;">+0 punten</p>
                <?php elseif ($type === 'given_up'): ?>
                    <div class="big-emoji">🏳️</div>
                    <h1>Kamer opgegeven</h1>
                    <p style="font-size:1.2rem;font-weight:700;color:#cbd5e1;">+0 punten</p>
                <?php else: ?>
                    <div class="big-emoji">⚠️</div>
                    <h1><?= View::e($feedback['message'] ?? 'Er ging iets mis.') ?></h1>
                <?php endif; ?>

                <?php if (in_array($type, ['correct', 'failed', 'given_up'], true)): ?>
                    <div style="margin-top:2rem;">
                        <?php if (!empty($feedback['next_room_name'])): ?>
                            <p style="color:#cbd5e1;font-size:.9rem;">Ga nu naar:</p>
                            <p style="font-size:1.5rem;font-weight:800;"><?= View::e(mb_strtoupper($feedback['next_room_name'])) ?>
                            </p>
                        <?php elseif (!empty($feedback['all_rooms_played'])): ?>
                            <p>Jullie hebben alle kamers gespeeld!</p>
                        <?php elseif (!empty($feedback['waiting'])): ?>
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
                    <p style="color:#cbd5e1;font-size:.85rem;">Jullie score</p>
                    <p style="font-size:1.75rem;font-weight:800;color:#fbbf24;"><?= (int) $ownResult['points'] ?> punten</p>
                    <p style="color:#cbd5e1;font-size:.85rem;margin-top:1rem;">Actieve speeltijd</p>
                    <p style="font-size:1.2rem;font-weight:700;"><?= fmtDur($ownResult['active_seconds']) ?></p>
                    <p style="color:#cbd5e1;font-size:.85rem;margin-top:1rem;">Kamers gespeeld</p>
                    <p style="font-size:1.2rem;font-weight:700;"><?= (int) $ownResult['rooms_played'] ?></p>
                </div>
                <p style="color:#cbd5e1;">De uitslag wordt straks bekendgemaakt.</p>
            </div>

        <?php elseif ($state === 'all_played'): ?>
            <div class="text-center" style="margin-top:3rem;">
                <div class="big-emoji">🎉</div>
                <h1>Jullie hebben alle kamers gespeeld!</h1>
                <p style="color:#cbd5e1;">Wacht op het einde van het evenement voor de uitslag.</p>
            </div>

        <?php elseif ($state === 'waiting'): ?>
            <div class="text-center" style="margin-top:3rem;">
                <div class="big-emoji">🚪</div>
                <h1>Geen kamer beschikbaar</h1>
                <p style="color:#cbd5e1;">Er is momenteel geen beschikbare kamer. Klik op vernieuwen om te kijken of er een
                    kamer is vrijgekomen.</p>
                <a href="/play" class="btn w-full" style="display:block;margin-top:2rem;">Vernieuwen</a>
            </div>

        <?php elseif ($state === 'assigned'): ?>
            <div class="text-center" style="margin-top:2rem;">
                <p style="text-transform:uppercase;letter-spacing:.1em;color:#cbd5e1;font-size:.85rem;">Jullie volgende
                    kamer</p>
                <h1 style="font-size:2rem;"><?= View::e(mb_strtoupper($room['name'])) ?></h1>
                <p style="color:#cbd5e1;">Ga naar <?= View::e($room['name']) ?>.</p>
                <?php if ($onderweg = Html::render($room['route_instructions'] ?? null)): ?>
                    <div class="room-box rich" style="text-align:left;"><?= $onderweg ?></div>
                <?php endif; ?>
                <form method="POST" action="/play/start" style="margin-top:2rem;">
                    <?= Csrf::field() ?>
                    <button type="submit" class="w-full" style="padding:1.2rem;font-size:1.1rem;">KAMER STARTEN</button>
                </form>
            </div>

        <?php elseif ($state === 'pending_review'): ?>
            <div class="text-center" style="margin-top:2rem;">
                <div class="big-emoji">🕵️</div>
                <h1>Antwoord wordt beoordeeld</h1>
                <p style="color:#cbd5e1;">Jullie foto is verstuurd. De organisatie beoordeelt hem zo snel mogelijk. Deze pagina ververst vanzelf zodra de uitslag bekend is.</p>
                <?php if ($pendingImagePath): ?>
                    <img src="/storage/answers/<?= View::e($pendingImagePath) ?>" alt="" style="max-width:100%;border-radius:.75rem;margin-top:1rem;">
                <?php endif; ?>
            </div>

        <?php elseif ($state === 'active'): ?>
            <p style="text-align:center;text-transform:uppercase;letter-spacing:.1em;color:#cbd5e1;font-size:.85rem;">
                <?= View::e(mb_strtoupper($room['name'])) ?>
            </p>
            <h1 style="text-align:center;font-size:1.1rem;">OPDRACHT</h1>

            <div class="room-box rich">
                <?php if (!empty($room['description'])): ?>
                    <p><?= View::e($room['description']) ?></p><?php endif; ?>
                <?= Html::render($room['instructions'] ?? null) ?>
            </div>

            <div class="attempts-row">
                <span>Pogingen: <?= (int) $attemptsUsed ?> / 3</span>
                <span id="active-timer" data-elapsed="<?= (int) $activeDurationSeconds ?>">Tijd:
                    <?= fmtDur($activeDurationSeconds) ?></span>
            </div>

            <?php if ($feedback && $feedback['type'] === 'incorrect'):
                $remaining = (int) $feedback['attempts_remaining']; ?>
                <div class="error-box">
                    Helaas, dit antwoord is niet goed.
                    Nog <?= $remaining ?>         <?= $remaining === 1 ? 'poging' : 'pogingen' ?>.
                </div>
            <?php endif; ?>

            <form method="POST" action="/play/answer">
                <?= Csrf::field() ?>
                <label for="answer">Antwoord:</label>
                <input id="answer" name="answer" type="text" autocomplete="off" autofocus required>
                <button type="submit" class="w-full">ANTWOORD CONTROLEREN</button>
            </form>

            <?php if ((int) ($room['allow_image_answer'] ?? 0) === 1): ?>
                <form method="POST" action="/play/answer-image" enctype="multipart/form-data" style="margin-top:1rem;">
                    <?= Csrf::field() ?>
                    <label for="image">Of stuur een foto als antwoord:</label>
                    <input id="image" name="image" type="file" accept="image/*" capture="environment" required style="margin-bottom:1rem;">
                    <button type="submit" class="w-full secondary">FOTO INSTUREN</button>
                </form>
                <p style="font-size:.8rem;color:#cbd5e1;text-align:center;margin-top:.5rem;">Een foto wordt door de organisatie bekeken en telt ook als een poging.</p>
            <?php endif; ?>

            <form method="POST" action="/play/give-up"
                onsubmit="return confirm('Weet je zeker dat je deze kamer wilt opgeven? Je krijgt 0 punten en kunt de kamer niet opnieuw spelen.');"
                style="margin-top:1.5rem;text-align:center;">
                <?= Csrf::field() ?>
                <button type="submit"
                    style="background:none;color:#f87171;text-decoration:underline;font-weight:600;font-size:.9rem;">KAMER
                    OPGEVEN</button>
            </form>
        <?php endif; ?>

    </main>

    <?php if ($supportPhone || $chatEnabled): ?>
        <button type="button" id="help-toggle" class="help-toggle" aria-label="Hulp">💬</button>
        <div id="help-panel" class="help-panel" hidden>
            <div class="help-panel-head">
                <strong>Hulp nodig?</strong>
                <button type="button" id="help-close" aria-label="Sluiten">✕</button>
            </div>

            <?php if ($supportPhone): ?>
                <a href="tel:<?= View::e(preg_replace('/\s+/', '', $supportPhone)) ?>" class="btn w-full" style="display:block;margin-bottom:.5rem;">
                    📞 Bel <?= View::e($supportPhone) ?>
                </a>
                <?php if ($supportNote): ?><p style="font-size:.8rem;color:#cbd5e1;margin:-.25rem 0 .75rem;"><?= View::e($supportNote) ?></p><?php endif; ?>
            <?php endif; ?>

            <?php if ($chatEnabled): ?>
                <div id="chat-log" class="chat-log">
                    <?php foreach ($chatMessages as $m): $mine = $m['sender'] === 'team'; ?>
                        <div class="chat-bubble <?= $mine ? 'mine' : 'theirs' ?>"><?= nl2br(View::e($m['body'])) ?></div>
                    <?php endforeach; ?>
                    <?php if ($chatMessages === []): ?><p style="font-size:.8rem;color:#94a3b8;">Stel hier je vraag aan de organisatie.</p><?php endif; ?>
                </div>
                <form method="POST" action="/play/chat" style="display:flex;gap:.4rem;margin-top:.5rem;">
                    <?= Csrf::field() ?>
                    <input type="text" name="body" placeholder="Typ een bericht…" autocomplete="off" required style="margin:0;flex:1;">
                    <button type="submit">&rarr;</button>
                </form>
            <?php endif; ?>
        </div>
        <script>
            (() => {
                const toggle = document.getElementById('help-toggle');
                const panel = document.getElementById('help-panel');
                const close = document.getElementById('help-close');
                const log = document.getElementById('chat-log');
                const open = () => { panel.hidden = false; if (log) log.scrollTop = log.scrollHeight; };
                toggle.addEventListener('click', open);
                close.addEventListener('click', () => { panel.hidden = true; });
            })();
        </script>
    <?php endif; ?>

    <?php if ($state !== 'finished'): ?>
        <script>
            // Geen blinde herlaadlus meer: we vragen alleen of er iets veranderd is
            // (nieuwe kamer van de organisator, sessie gereset, spel gestart of
            // afgelopen) en verversen pas als dat zo is. Typ je een antwoord, dan
            // blijft dat gewoon staan zolang er niets wijzigt.
            (() => {
                const bekend = <?= json_encode($fingerprint) ?>;
                setInterval(async () => {
                    try {
                        const antwoord = await fetch('/play/status', { headers: { Accept: 'application/json' } });
                        if (!antwoord.ok) return;
                        const data = await antwoord.json();
                        if (data.fingerprint !== bekend) window.location.reload();
                    } catch (e) {
                        // Netwerk even weg: gewoon de volgende ronde opnieuw proberen.
                    }
                }, 3000);
            })();
        </script>
    <?php endif; ?>

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