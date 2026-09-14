<?php

use App\Csrf;
use App\View;

/** @var array $game */
$startValue = $game['start_time'] ? str_replace(' ', 'T', substr($game['start_time'], 0, 16)) : '';
$endValue = $game['end_time'] ? str_replace(' ', 'T', substr($game['end_time'], 0, 16)) : '';
?>
<h1 style="margin-bottom:.25rem;"><?= View::e($game['name']) ?></h1>
<p style="color:#64748b;margin-bottom:1.5rem;">Status: <strong><?= View::e(ucfirst($game['status'])) ?></strong></p>

<div class="grid-2">
    <div class="panel">
        <h2>Instellingen</h2>
        <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>">
            <?= Csrf::field() ?>
            <label for="name">Naam</label>
            <input id="name" name="name" type="text" value="<?= View::e($game['name']) ?>" required>
            <label for="start_time">Starttijd</label>
            <input id="start_time" name="start_time" type="datetime-local" value="<?= View::e($startValue) ?>">
            <label for="end_time">Eindtijd</label>
            <input id="end_time" name="end_time" type="datetime-local" value="<?= View::e($endValue) ?>">
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
                <button type="submit" class="w-full" <?= ($game['status'] !== 'draft' || $game['end_time'] === null) ? 'disabled' : '' ?> style="background:#059669;color:white;">
                    Game starten
                </button>
                <?php if ($game['status'] === 'draft' && $game['end_time'] === null): ?>
                    <p style="font-size:.8rem;color:#64748b;margin-top:.25rem;">Stel eerst een eindtijd in hierboven.</p>
                <?php endif; ?>
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
