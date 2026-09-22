<?php

use App\Csrf;
use App\View;

/** @var array $game */
$endValue = $game['end_time'] ? date('d-m-Y H:i', strtotime($game['end_time'])) : '';
?>
<h1 style="margin-bottom:.25rem;"><?= View::e($game['name']) ?></h1>
<p style="color:#475569;margin-bottom:1.5rem;">Status: <strong><?= View::e(ucfirst($game['status'])) ?></strong></p>

<div class="grid-2">
    <div class="panel">
        <h2>Instellingen</h2>
        <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>">
            <?= Csrf::field() ?>
            <label for="name">Naam</label>
            <input id="name" name="name" type="text" value="<?= View::e($game['name']) ?>" required>
            <label for="end_time">Eindtijd</label>
            <input id="end_time" name="end_time" type="text" readonly autocomplete="off"
                   placeholder="Klik om datum en tijd te kiezen" value="<?= View::e($endValue) ?>">
            <button type="submit" class="w-full">Opslaan</button>
        </form>
    </div>

    <div>
        <div class="panel">
            <h2>Overzicht</h2>
            <ul>
                <li><a href="/admin/games/<?= (int) $game['id'] ?>/teams">Teams beheren</a></li>
                <li><a href="/admin/games/<?= (int) $game['id'] ?>/rooms">Kamers beheren</a></li>
                <li><a href="/admin/games/<?= (int) $game['id'] ?>/dashboard">Live dashboard bekijken</a></li>
            </ul>
        </div>

        <div class="panel">
            <h2>Spelbeheer</h2>

            <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/start" onsubmit="return confirm('Game starten?');" style="margin-bottom:.6rem;">
                <?= Csrf::field() ?>
                <button type="submit" class="w-full" <?= $game['status'] !== 'draft' ? 'disabled' : '' ?> style="background:#059669;color:white;">
                    Game starten
                </button>
            </form>

            <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/finish" onsubmit="return confirm('Weet je zeker dat je het game nu wilt beëindigen?');" style="margin-bottom:.6rem;">
                <?= Csrf::field() ?>
                <button type="submit" class="w-full danger" <?= $game['status'] !== 'running' ? 'disabled' : '' ?>>Game beëindigen</button>
            </form>

            <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/reset" onsubmit="return confirm('Alle voortgang (sessies, punten, pogingen) wordt gewist. Doorgaan?');" style="margin-bottom:.6rem;">
                <?= Csrf::field() ?>
                <button type="submit" class="w-full secondary">Game resetten</button>
            </form>

            <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/delete" onsubmit="return confirm('Dit game inclusief teams en kamers permanent verwijderen?');">
                <?= Csrf::field() ?>
                <button type="submit" class="w-full" style="background:none;color:#b91c1c;text-decoration:underline;">Game verwijderen</button>
            </form>
        </div>
    </div>
</div>

<div class="overlay" id="dtp" hidden>
    <div class="panel dtp">
        <div class="dtp-head">
            <button type="button" class="dtp-nav" id="dtp-prev" aria-label="Vorige maand">&lsaquo;</button>
            <strong id="dtp-title"></strong>
            <button type="button" class="dtp-nav" id="dtp-next" aria-label="Volgende maand">&rsaquo;</button>
        </div>
        <div class="dtp-weekdays">
            <span>ma</span><span>di</span><span>wo</span><span>do</span><span>vr</span><span>za</span><span>zo</span>
        </div>
        <div class="dtp-days" id="dtp-days"></div>
        <div class="dtp-time">
            <label for="dtp-hour">Tijd (24-uurs)</label>
            <select id="dtp-hour" aria-label="Uur"></select>
            <span>:</span>
            <select id="dtp-minute" aria-label="Minuut"></select>
        </div>
        <div class="dtp-actions">
            <button type="button" class="secondary" id="dtp-clear">Wissen</button>
            <button type="button" id="dtp-ok">Klaar</button>
        </div>
    </div>
</div>

<script>
(() => {
    const MAANDEN = ['januari', 'februari', 'maart', 'april', 'mei', 'juni',
        'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
    const pad = (n) => String(n).padStart(2, '0');

    const input = document.getElementById('end_time');
    const dialog = document.getElementById('dtp');
    const title = document.getElementById('dtp-title');
    const daysEl = document.getElementById('dtp-days');
    const hourEl = document.getElementById('dtp-hour');
    const minuteEl = document.getElementById('dtp-minute');

    for (let h = 0; h <= 23; h++) hourEl.add(new Option(pad(h), h));
    for (let m = 0; m <= 59; m++) minuteEl.add(new Option(pad(m), m));

    let selected = null;  // Date, of null zolang er niets gekozen is
    let view = new Date();

    function readInput() {
        const match = input.value.match(/^(\d{2})-(\d{2})-(\d{4}) (\d{2}):(\d{2})$/);
        if (!match) return null;
        const [, d, mo, y, h, mi] = match.map(Number);
        return new Date(y, mo - 1, d, h, mi);
    }

    function drawDays() {
        const year = view.getFullYear();
        const month = view.getMonth();
        title.textContent = `${MAANDEN[month]} ${year}`;
        daysEl.textContent = '';

        // getDay() telt vanaf zondag; de Nederlandse week begint op maandag.
        const offset = (new Date(year, month, 1).getDay() + 6) % 7;
        const total = new Date(year, month + 1, 0).getDate();
        const today = new Date();

        for (let i = 0; i < offset; i++) {
            const filler = document.createElement('span');
            filler.className = 'dtp-day empty';
            daysEl.append(filler);
        }

        for (let day = 1; day <= total; day++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dtp-day';
            button.textContent = day;

            if (today.getFullYear() === year && today.getMonth() === month && today.getDate() === day) {
                button.classList.add('today');
            }
            if (selected && selected.getFullYear() === year && selected.getMonth() === month && selected.getDate() === day) {
                button.classList.add('selected');
            }

            button.addEventListener('click', () => {
                selected = new Date(year, month, day);
                drawDays();
            });
            daysEl.append(button);
        }
    }

    function open() {
        const current = readInput();
        selected = current;
        view = current ? new Date(current) : new Date();
        hourEl.value = current ? current.getHours() : 20;
        minuteEl.value = current ? current.getMinutes() : 0;
        drawDays();
        dialog.hidden = false;
    }

    // Openen op 'click' (dus na het loslaten van de muis): zou de kalender al
    // tijdens het indrukken verschijnen, dan landt diezelfde klik op een dag.
    input.addEventListener('click', open);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            open();
        }
    });

    document.getElementById('dtp-prev').addEventListener('click', () => {
        view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
        drawDays();
    });
    document.getElementById('dtp-next').addEventListener('click', () => {
        view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
        drawDays();
    });

    document.getElementById('dtp-ok').addEventListener('click', () => {
        if (selected) {
            input.value = `${pad(selected.getDate())}-${pad(selected.getMonth() + 1)}-${selected.getFullYear()} `
                + `${pad(hourEl.value)}:${pad(minuteEl.value)}`;
        }
        dialog.hidden = true;
        input.blur();
    });

    document.getElementById('dtp-clear').addEventListener('click', () => {
        input.value = '';
        dialog.hidden = true;
        input.blur();
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.hidden = true;
            input.blur();
        }
    });
})();
</script>
