<?php

use App\Csrf;
use App\View;

/** @var array $game */
/** @var array $team */
/** @var array $messages */
/** @var string $fingerprint */

$gameId = (int) $game['id'];
$teamId = (int) $team['id'];
?>

<p><a href="/admin/games/<?= $gameId ?>/chat">&larr; Alle teams</a></p>
<h1 style="margin-bottom:1rem;">Chat met <?= View::e($team['name']) ?></h1>

<div class="panel" id="chat-log" style="max-height:60vh;overflow-y:auto;display:flex;flex-direction:column;gap:.6rem;">
    <?php if ($messages === []): ?>
        <p style="color:#475569;">Nog geen berichten.</p>
    <?php endif; ?>
    <?php foreach ($messages as $m): $mine = $m['sender'] === 'admin'; ?>
        <div style="align-self:<?= $mine ? 'flex-end' : 'flex-start' ?>;max-width:75%;">
            <div style="background:<?= $mine ? '#0f172a' : '#e2e8f0' ?>;color:<?= $mine ? '#fff' : '#0f172a' ?>;padding:.6rem .9rem;border-radius:.9rem;">
                <?= nl2br(View::e($m['body'])) ?>
            </div>
            <p style="font-size:.7rem;color:#94a3b8;margin:.15rem .3rem;"><?= $mine ? 'Jij' : View::e($team['name']) ?> · <?= View::e($m['created_at']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<form method="POST" action="/admin/games/<?= $gameId ?>/chat/<?= $teamId ?>/send" style="display:flex;gap:.5rem;margin-top:1rem;">
    <?= Csrf::field() ?>
    <input type="text" name="body" placeholder="Typ een bericht…" autocomplete="off" required style="margin:0;flex:1;">
    <button type="submit">Versturen</button>
</form>

<script>
    (() => {
        const log = document.getElementById('chat-log');
        log.scrollTop = log.scrollHeight;

        const bekend = <?= json_encode($fingerprint) ?>;
        setInterval(async () => {
            try {
                const antwoord = await fetch('/admin/games/<?= $gameId ?>/chat/<?= $teamId ?>/status', { headers: { Accept: 'application/json' } });
                if (! antwoord.ok) return;
                const data = await antwoord.json();
                if (data.fingerprint !== bekend) window.location.reload();
            } catch (e) {}
        }, 3000);
    })();
</script>
