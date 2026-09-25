<?php

use App\Csrf;
use App\RoomSession;
use App\View;

/** @var array $game */
/** @var string $tab */
/** @var array $teamsOverview */
/** @var array $roomsOverview */
/** @var array $standings */
/** @var int $remainingEventSeconds */
/** @var int|null $manualAssignTeamId */
/** @var array $allRoomsForAssign */
/** @var int|null $resettingSessionId */
/** @var int|null $adjustingScoreSessionId */
/** @var array|null $adjustingSession */
/** @var int $pendingReviewCount */
/** @var int $unreadChatCount */
/** @var int $activeTeamsCount */
/** @var int $roomsFullCount */
/** @var string $fingerprint */

$gameId = (int) $game['id'];
$statusColor = match ($game['status']) {
    'running' => '#059669',
    'finished' => '#dc2626',
    default => '#475569',
};
function fmtDuration(int $seconds): string
{
    return gmdate('H:i:s', $seconds);
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
        <h1 style="margin-bottom:.1rem;"><?= View::e($game['name']) ?></h1>
        <p style="color:#475569;">Status: <strong style="color:<?= $statusColor ?>;"><?= View::e(ucfirst($game['status'])) ?></strong></p>
    </div>
    <?php if ($game['status'] === 'running'): ?>
        <div id="event-timer" data-remaining="<?= $remainingEventSeconds ?>" style="background:#0f172a;color:#fbbf24;padding:.5rem 1rem;border-radius:.75rem;font-family:monospace;font-size:1.1rem;">
            Nog <?= fmtDuration($remainingEventSeconds) ?>
        </div>
    <?php endif; ?>
</div>

<div class="summary-bar">
    <div class="summary-tile">
        <span class="summary-count"><?= $activeTeamsCount ?></span>
        <span class="summary-label">actieve teams</span>
    </div>
    <a href="/admin/games/<?= $gameId ?>/answers" class="summary-tile <?= $pendingReviewCount > 0 ? 'attention' : '' ?>">
        <span class="summary-count"><?= $pendingReviewCount ?></span>
        <span class="summary-label">foto's te beoordelen</span>
    </a>
    <a href="/admin/games/<?= $gameId ?>/chat" class="summary-tile <?= $unreadChatCount > 0 ? 'attention' : '' ?>">
        <span class="summary-count"><?= $unreadChatCount ?></span>
        <span class="summary-label">ongelezen chatberichten</span>
    </a>
    <div class="summary-tile">
        <span class="summary-count"><?= $roomsFullCount ?></span>
        <span class="summary-label">volle kamers</span>
    </div>
</div>

<div class="tabs">
    <a href="?tab=teams" class="<?= $tab === 'teams' ? 'active' : '' ?>">Teamoverzicht</a>
    <a href="?tab=rooms" class="<?= $tab === 'rooms' ? 'active' : '' ?>">Kamerbezetting</a>
    <a href="?tab=leaderboard" class="<?= $tab === 'leaderboard' ? 'active' : '' ?>">Leaderboard</a>
</div>

<?php if ($tab === 'teams'): ?>
    <table>
        <thead><tr><th>Team</th><th>Kamer</th><th>Status</th><th>Punten</th><th>Actieve tijd</th><th>Kamers gespeeld</th><th>Acties</th></tr></thead>
        <tbody>
        <?php foreach ($teamsOverview as $row): $team = $row['team']; ?>
            <tr style="<?= $team['active'] ? '' : 'background:#fef2f2;' ?>">
                <td>
                    <strong><?= View::e($team['name']) ?></strong>
                    <?php if (! $team['active']): ?><span class="pill red">geblokkeerd</span><?php endif; ?>
                </td>
                <td><?= $row['current_room']['name'] ?? '—' ?></td>
                <td><?= $row['current_session'] ? RoomSession::statusLabel($row['current_session']['status']) : 'Geen actieve kamer' ?></td>
                <td><strong><?= (int) $row['points'] ?></strong></td>
                <td style="font-family:monospace;"><?= fmtDuration($row['active_seconds']) ?></td>
                <td><?= (int) $row['rooms_played'] ?></td>
                <td class="actions">
                    <a href="?tab=teams&assign_team=<?= (int) $team['id'] ?>">Kamer toewijzen</a>
                    <?php if ($row['current_session']): ?>
                        <a href="?tab=teams&reset_session=<?= (int) $row['current_session']['id'] ?>">Reset sessie</a>
                        <a href="?tab=teams&adjust_score=<?= (int) $row['current_session']['id'] ?>">Score aanpassen</a>
                    <?php endif; ?>
                    <form class="inline-form" method="POST" action="/admin/games/<?= $gameId ?>/dashboard/toggle-team">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                        <button type="submit" class="secondary"><?= $team['active'] ? 'Blokkeer' : 'Activeer' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($tab === 'rooms'): ?>
    <div class="room-grid">
        <?php foreach ($roomsOverview as $room): $count = $room['active_teams_count']; $c = min($count, 3); ?>
            <div class="room-tile c<?= $c ?>" style="<?= $room['active'] ? '' : 'opacity:.4;' ?>">
                <p><strong><?= View::e($room['name']) ?></strong></p>
                <p class="count"><?= $count ?></p>
                <p style="font-size:.75rem;"><?= $count === 1 ? 'team' : 'teams' ?></p>
                <?php if (! $room['active']): ?><p style="font-size:.7rem;font-weight:700;">inactief</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($tab === 'leaderboard'): ?>
    <table>
        <thead><tr><th>#</th><th>Team</th><th>Punten</th><th>Actieve tijd</th></tr></thead>
        <tbody>
        <?php foreach ($standings as $row): ?>
            <tr>
                <td><strong><?= (int) $row['rank'] ?></strong></td>
                <td><strong><?= View::e($row['team']['name']) ?></strong></td>
                <td><strong><?= (int) $row['points'] ?></strong></td>
                <td style="font-family:monospace;"><?= fmtDuration($row['active_seconds']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="grid-2" style="margin-top:2rem;">
    <div>
        <h2 style="margin-bottom:.75rem;">Foto's ter beoordeling<?php if ($pendingReviewCount > 0): ?> <span class="pill red"><?= $pendingReviewCount ?></span><?php endif; ?></h2>
        <?php if ($pendingAnswers === []): ?>
            <div class="panel" style="color:#475569;">Niets te beoordelen.</div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:.75rem;">
                <?php foreach ($pendingAnswers as $row): $attempt = $row['attempt']; ?>
                    <div class="panel" style="margin-bottom:0;">
                        <p style="font-size:.7rem;color:#475569;text-transform:uppercase;font-weight:700;margin:0 0 .4rem;">
                            <?= View::e($row['team']['name'] ?? '?') ?> · <?= View::e($row['room']['name'] ?? '?') ?> · poging <?= (int) $attempt['attempt_number'] ?>
                        </p>
                        <a href="/storage/answers/<?= View::e($attempt['image_path']) ?>" target="_blank">
                            <img src="/storage/answers/<?= View::e($attempt['image_path']) ?>" alt="" style="width:100%;border-radius:.5rem;display:block;">
                        </a>
                        <div style="display:flex;gap:.5rem;margin-top:.6rem;">
                            <form method="POST" action="/admin/games/<?= $gameId ?>/answers/<?= (int) $attempt['id'] ?>/approve" style="flex:1;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="redirect" value="/admin/games/<?= $gameId ?>/dashboard?tab=<?= $tab ?>">
                                <button type="submit" class="w-full">Goedkeuren</button>
                            </form>
                            <form method="POST" action="/admin/games/<?= $gameId ?>/answers/<?= (int) $attempt['id'] ?>/reject" style="flex:1;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="redirect" value="/admin/games/<?= $gameId ?>/dashboard?tab=<?= $tab ?>">
                                <button type="submit" class="w-full danger">Afkeuren</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <h2 style="margin-bottom:.75rem;">Chat<?php if ($unreadChatCount > 0): ?> <span class="pill red"><?= $unreadChatCount ?></span><?php endif; ?></h2>
        <?php if ($chatRows === []): ?>
            <div class="panel" style="color:#475569;">Nog geen gesprekken.</div>
        <?php else: ?>
            <?php $lastChatIndex = array_key_last($chatRows); ?>
            <div class="panel" style="padding:0;max-height:26rem;overflow-y:auto;">
                <?php foreach ($chatRows as $i => $row): $team = $row['team']; $last = $row['last']; ?>
                    <div style="padding:.75rem 1rem;<?= $i === $lastChatIndex ? '' : 'border-bottom:1px solid #e2e8f0;' ?>">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <strong><?= View::e($team['name']) ?></strong>
                            <span>
                                <?php if ($row['unread'] > 0): ?><span class="pill red"><?= $row['unread'] ?></span><?php endif; ?>
                                <a href="/admin/games/<?= $gameId ?>/chat/<?= (int) $team['id'] ?>" style="font-size:.8rem;">Open</a>
                            </span>
                        </div>
                        <p style="font-size:.85rem;color:#475569;margin:.2rem 0 .5rem;">
                            <?= $last['sender'] === 'admin' ? 'Jij: ' : '' ?><?= View::e(mb_strimwidth($last['body'], 0, 80, '…')) ?>
                        </p>
                        <form method="POST" action="/admin/games/<?= $gameId ?>/chat/<?= (int) $team['id'] ?>/send" style="display:flex;gap:.4rem;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="redirect" value="/admin/games/<?= $gameId ?>/dashboard?tab=<?= $tab ?>">
                            <input type="text" name="body" placeholder="Snel antwoorden…" autocomplete="off" required style="margin:0;flex:1;font-size:.85rem;padding:.4rem .6rem;">
                            <button type="submit" style="padding:.4rem .8rem;font-size:.85rem;">&rarr;</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($manualAssignTeamId): ?>
    <div class="overlay">
    <div class="panel" style="max-width:28rem;">
        <h2>Kies een kamer</h2>
        <?php foreach ($allRoomsForAssign as $room): ?>
            <form method="POST" action="/admin/games/<?= $gameId ?>/dashboard/assign-room" style="margin-bottom:.5rem;">
                <?= Csrf::field() ?>
                <input type="hidden" name="team_id" value="<?= $manualAssignTeamId ?>">
                <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
                <button type="submit" class="w-full secondary" style="text-align:left;"><?= View::e($room['name']) ?></button>
            </form>
        <?php endforeach; ?>
        <a href="?tab=<?= $tab ?>" class="btn secondary w-full" style="display:block;margin-top:.5rem;">Annuleren</a>
    </div>
    </div>
<?php endif; ?>

<?php if ($resettingSessionId): ?>
    <div class="overlay">
    <div class="panel" style="max-width:28rem;text-align:center;">
        <p><strong>Weet je zeker dat je deze sessie wilt resetten?</strong></p>
        <p style="font-size:.85rem;color:#475569;">De sessie wordt verwijderd en het team krijgt automatisch een nieuwe kamer.</p>
        <div style="display:flex;gap:.5rem;margin-top:1rem;">
            <form method="POST" action="/admin/games/<?= $gameId ?>/dashboard/reset-session" style="flex:1;">
                <?= Csrf::field() ?>
                <input type="hidden" name="session_id" value="<?= $resettingSessionId ?>">
                <button type="submit" class="w-full danger">Resetten</button>
            </form>
            <a href="?tab=<?= $tab ?>" class="btn secondary" style="flex:1;">Annuleren</a>
        </div>
    </div>
    </div>
<?php endif; ?>

<?php if ($adjustingScoreSessionId && $adjustingSession): ?>
    <div class="overlay">
    <div class="panel" style="max-width:28rem;">
        <h2>Score aanpassen</h2>
        <form method="POST" action="/admin/games/<?= $gameId ?>/dashboard/adjust-score">
            <?= Csrf::field() ?>
            <input type="hidden" name="session_id" value="<?= $adjustingScoreSessionId ?>">
            <input type="number" name="points" min="0" max="3" value="<?= (int) $adjustingSession['points'] ?>">
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="w-full">Opslaan</button>
                <a href="?tab=<?= $tab ?>" class="btn secondary w-full">Annuleren</a>
            </div>
        </form>
    </div>
    </div>
<?php endif; ?>

<script>
    // Ververst alleen als er echt iets veranderde (team loste een kamer op,
    // kreeg een nieuwe kamer, werd geblokkeerd). Staat er een venster open of
    // typt de beheerder net een chatbericht, dan wachten we: anders verdwijnt
    // wat er net ingetypt werd.
    (() => {
        const bekend = <?= json_encode($fingerprint) ?>;
        setInterval(async () => {
            if (document.querySelector('.overlay')) return;
            if (document.activeElement && ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
            try {
                const antwoord = await fetch('<?= "/admin/games/$gameId/dashboard/status" ?>', { headers: { Accept: 'application/json' } });
                if (! antwoord.ok) return;
                const data = await antwoord.json();
                if (data.fingerprint !== bekend) window.location.reload();
            } catch (e) {
                // Netwerk even weg: volgende ronde opnieuw proberen.
            }
        }, 3000);
    })();
</script>

<script>
    // Cosmetisch doortikken; de server blijft de enige bron van waarheid.
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
</script>
