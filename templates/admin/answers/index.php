<?php

use App\Csrf;
use App\View;

/** @var array $game */
/** @var array $rows */
/** @var string $fingerprint */

$gameId = (int) $game['id'];
?>

<h1 style="margin-bottom:.25rem;">Antwoorden beoordelen</h1>
<p style="color:#475569;margin-top:0;">Foto's die teams als antwoord hebben ingestuurd, wachten hier op een oordeel. Zodra je goed- of afkeurt, ziet het team direct de uitslag.</p>

<?php if ($rows === []): ?>
    <div class="panel" style="text-align:center;color:#475569;">Geen foto's die wachten op beoordeling.</div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:1rem;">
        <?php foreach ($rows as $row):
            $attempt = $row['attempt'];
            $room = $row['room'];
            $team = $row['team']; ?>
            <div class="panel" style="margin-bottom:0;">
                <p style="font-size:.75rem;color:#475569;text-transform:uppercase;font-weight:700;margin:0 0 .25rem;">
                    <?= View::e($team['name'] ?? '?') ?> · <?= View::e($room['name'] ?? '?') ?> · poging <?= (int) $attempt['attempt_number'] ?>
                </p>
                <a href="/storage/answers/<?= View::e($attempt['image_path']) ?>" target="_blank">
                    <img src="/storage/answers/<?= View::e($attempt['image_path']) ?>" alt="" style="width:100%;border-radius:.5rem;display:block;">
                </a>
                <div style="display:flex;gap:.5rem;margin-top:.75rem;">
                    <form method="POST" action="/admin/games/<?= $gameId ?>/answers/<?= (int) $attempt['id'] ?>/approve" style="flex:1;">
                        <?= Csrf::field() ?>
                        <button type="submit" class="w-full">Goedkeuren</button>
                    </form>
                    <form method="POST" action="/admin/games/<?= $gameId ?>/answers/<?= (int) $attempt['id'] ?>/reject" style="flex:1;">
                        <?= Csrf::field() ?>
                        <button type="submit" class="w-full danger">Afkeuren</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    (() => {
        const bekend = <?= json_encode($fingerprint) ?>;
        setInterval(async () => {
            try {
                const antwoord = await fetch('/admin/games/<?= $gameId ?>/answers/status', { headers: { Accept: 'application/json' } });
                if (! antwoord.ok) return;
                const data = await antwoord.json();
                if (data.fingerprint !== bekend) window.location.reload();
            } catch (e) {}
        }, 3000);
    })();
</script>
