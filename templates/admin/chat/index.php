<?php

use App\View;

/** @var array $game */
/** @var array $rows */
/** @var string $fingerprint */

$gameId = (int) $game['id'];
?>

<h1>Chat met teams</h1>

<table>
    <thead><tr><th>Team</th><th>Laatste bericht</th><th>Ongelezen</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): $team = $row['team']; $last = $row['last']; ?>
        <tr>
            <td><strong><?= View::e($team['name']) ?></strong></td>
            <td style="color:#475569;">
                <?php if ($last): ?>
                    <?= $last['sender'] === 'admin' ? 'Jij: ' : '' ?><?= View::e(mb_strimwidth($last['body'], 0, 60, '…')) ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td><?php if ($row['unread'] > 0): ?><span class="pill red"><?= $row['unread'] ?></span><?php endif; ?></td>
            <td><a href="/admin/games/<?= $gameId ?>/chat/<?= (int) $team['id'] ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
    (() => {
        const bekend = <?= json_encode($fingerprint) ?>;
        setInterval(async () => {
            try {
                const antwoord = await fetch('/admin/games/<?= $gameId ?>/chat/status', { headers: { Accept: 'application/json' } });
                if (! antwoord.ok) return;
                const data = await antwoord.json();
                if (data.fingerprint !== bekend) window.location.reload();
            } catch (e) {}
        }, 3000);
    })();
</script>
