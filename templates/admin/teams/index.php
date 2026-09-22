<?php

use App\Csrf;
use App\View;

/** @var array $game */
/** @var array $teams */
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Teams — <?= View::e($game['name']) ?></h1>
    <a href="/admin/games/<?= (int) $game['id'] ?>/teams/create" class="btn">+ Team toevoegen</a>
</div>

<table>
    <thead><tr><th>Naam</th><th>Code</th><th>Status</th><th>Sessies</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($teams)): ?>
            <tr><td colspan="5" style="text-align:center;color:#475569;padding:1.5rem;">Nog geen teams.</td></tr>
        <?php else: foreach ($teams as $team): ?>
            <tr>
                <td><strong><?= View::e($team['name']) ?></strong></td>
                <td style="font-family:monospace;"><?= View::e($team['code'] ?? '—') ?></td>
                <td><span class="pill <?= $team['active'] ? 'green' : 'red' ?>"><?= $team['active'] ? 'Actief' : 'Geblokkeerd' ?></span></td>
                <td><?= (int) $team['room_sessions_count'] ?></td>
                <td class="actions">
                    <a href="/admin/games/<?= (int) $game['id'] ?>/teams/<?= (int) $team['id'] ?>/edit">Bewerken</a>
                    <form class="inline-form" method="POST" action="/admin/games/<?= (int) $game['id'] ?>/teams/<?= (int) $team['id'] ?>/reset-code">
                        <?= Csrf::field() ?>
                        <button type="submit" class="secondary">Code resetten</button>
                    </form>
                    <form class="inline-form" method="POST" action="/admin/games/<?= (int) $game['id'] ?>/teams/<?= (int) $team['id'] ?>/toggle-active">
                        <?= Csrf::field() ?>
                        <button type="submit" class="secondary"><?= $team['active'] ? 'Blokkeren' : 'Activeren' ?></button>
                    </form>
                    <form class="inline-form" method="POST" action="/admin/games/<?= (int) $game['id'] ?>/teams/<?= (int) $team['id'] ?>/delete" onsubmit="return confirm('Team en alle bijbehorende sessies verwijderen?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="danger">Verwijderen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
