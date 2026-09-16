<?php

use App\View;

/** @var array $games */
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Games</h1>
    <a href="/admin/games/create" class="btn">+ Nieuw game</a>
</div>

<table>
    <thead>
        <tr><th>Naam</th><th>Status</th><th>Teams</th><th>Kamers</th><th>Einde</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($games)): ?>
            <tr><td colspan="6" style="text-align:center;color:#64748b;padding:1.5rem;">Nog geen games aangemaakt.</td></tr>
        <?php else: foreach ($games as $game): ?>
            <tr>
                <td><strong><?= View::e($game['name']) ?></strong></td>
                <td><?= View::e(ucfirst($game['status'])) ?></td>
                <td><?= (int) $game['teams_count'] ?></td>
                <td><?= (int) $game['rooms_count'] ?></td>
                <td><?= $game['end_time'] ? View::e(date('d-m-Y H:i', strtotime($game['end_time']))) : '—' ?></td>
                <td class="actions">
                    <a href="/admin/games/<?= (int) $game['id'] ?>/dashboard">Dashboard</a>
                    <a href="/admin/games/<?= (int) $game['id'] ?>/edit">Beheer</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
