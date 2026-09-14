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

$gameId = (int) $game['id'];
$statusColor = match ($game['status']) {
    'running' => '#059669',
    'finished' => '#dc2626',
    default => '#64748b',
};
function fmtDuration(int $seconds): string
{
    return gmdate('H:i:s', $seconds);
}
?>
<script>
    // The joke's on us: no wire:poll here, just the oldest trick in the book.
    setTimeout(() => window.location.reload(), 5000);
</script>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
        <h1 style="margin-bottom:.1rem;"><?= View::e($game['name']) ?></h1>
        <p style="color:#64748b;">Status: <strong style="color:<?= $statusColor ?>;"><?= View::e(ucfirst($game['status'])) ?></strong></p>
    </div>
    <?php if ($game['status'] === 'running'): ?>
        <div style="background:#0f172a;color:#fbbf24;padding:.5rem 1rem;border-radius:.75rem;font-family:monospace;font-size:1.1rem;">
            Nog <?= fmtDuration($remainingEventSeconds) ?>
        </div>
    <?php endif; ?>
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

<?php if ($manualAssignTeamId): ?>
    <div class="panel" style="margin-top:1.5rem;max-width:28rem;">
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
<?php endif; ?>

<?php if ($resettingSessionId): ?>
    <div class="panel" style="margin-top:1.5rem;max-width:28rem;text-align:center;">
        <p><strong>Weet je zeker dat je deze sessie wilt resetten?</strong></p>
        <p style="font-size:.85rem;color:#64748b;">De sessie wordt verwijderd en het team krijgt automatisch een nieuwe kamer.</p>
        <div style="display:flex;gap:.5rem;margin-top:1rem;">
            <form method="POST" action="/admin/games/<?= $gameId ?>/dashboard/reset-session" style="flex:1;">
                <?= Csrf::field() ?>
                <input type="hidden" name="session_id" value="<?= $resettingSessionId ?>">
                <button type="submit" class="w-full danger">Resetten</button>
            </form>
            <a href="?tab=<?= $tab ?>" class="btn secondary" style="flex:1;">Annuleren</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($adjustingScoreSessionId && $adjustingSession): ?>
    <div class="panel" style="margin-top:1.5rem;max-width:28rem;">
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
<?php endif; ?>
