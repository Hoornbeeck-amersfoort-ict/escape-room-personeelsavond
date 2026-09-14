<?php

use App\Csrf;
use App\View;

/** @var array $game */
/** @var array $rooms */
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Kamers — <?= View::e($game['name']) ?></h1>
    <a href="/admin/games/<?= (int) $game['id'] ?>/rooms/create" class="btn">+ Kamer toevoegen</a>
</div>

<table>
    <thead><tr><th>Naam</th><th>Antwoord</th><th>Status</th><th>Sessies</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($rooms)): ?>
            <tr><td colspan="5" style="text-align:center;color:#64748b;padding:1.5rem;">Nog geen kamers.</td></tr>
        <?php else: foreach ($rooms as $room): ?>
            <tr>
                <td><strong><?= View::e($room['name']) ?></strong></td>
                <td style="font-family:monospace;font-size:.8rem;"><?= View::e($room['answer']) ?></td>
                <td><span class="pill <?= $room['active'] ? 'green' : 'red' ?>"><?= $room['active'] ? 'Actief' : 'Inactief' ?></span></td>
                <td><?= (int) $room['room_sessions_count'] ?></td>
                <td class="actions">
                    <a href="/admin/games/<?= (int) $game['id'] ?>/rooms/<?= (int) $room['id'] ?>/edit">Bewerken</a>
                    <form class="inline-form" method="POST" action="/admin/games/<?= (int) $game['id'] ?>/rooms/<?= (int) $room['id'] ?>/toggle-active">
                        <?= Csrf::field() ?>
                        <button type="submit" class="secondary"><?= $room['active'] ? 'Deactiveren' : 'Activeren' ?></button>
                    </form>
                    <form class="inline-form" method="POST" action="/admin/games/<?= (int) $game['id'] ?>/rooms/<?= (int) $room['id'] ?>/delete" onsubmit="return confirm('Kamer en alle bijbehorende sessies verwijderen?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="danger">Verwijderen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
