<?php

use App\View;

/** @var array $logs */
?>
<h1>Audit log</h1>
<table>
    <thead><tr><th>Datum</th><th>Admin</th><th>Actie</th><th>Doel</th><th>Oud</th><th>Nieuw</th></tr></thead>
    <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="6" style="text-align:center;color:#64748b;padding:1.5rem;">Nog geen handmatige acties gelogd.</td></tr>
        <?php else: foreach ($logs as $log): ?>
            <tr>
                <td style="white-space:nowrap;"><?= View::e(date('d-m-Y H:i:s', strtotime($log['created_at']))) ?></td>
                <td><?= View::e($log['admin']['name'] ?? '—') ?></td>
                <td style="font-family:monospace;font-size:.8rem;"><?= View::e($log['action']) ?></td>
                <td style="font-family:monospace;font-size:.8rem;"><?= View::e(class_basename($log['target_type'])) ?> #<?= (int) $log['target_id'] ?></td>
                <td style="font-family:monospace;font-size:.8rem;color:#64748b;"><?= View::e($log['old_values'] && $log['old_values'] !== '[]' ? $log['old_values'] : '—') ?></td>
                <td style="font-family:monospace;font-size:.8rem;color:#64748b;"><?= View::e($log['new_values'] && $log['new_values'] !== '[]' ? $log['new_values'] : '—') ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
