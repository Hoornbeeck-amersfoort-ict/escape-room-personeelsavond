<?php

use App\View;

/** @var string $title */
/** @var array|null $game */
/** @var string $content */
$flash = View::pullFlash();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title) ?> — Escape Room Admin</title>
<link rel="stylesheet" href="<?= asset('/style.css') ?>">
</head>
<body>
<div class="admin-shell">
    <div class="sidebar">
        <div class="brand">🔑 Escape Room</div>
        <nav>
            <a href="/admin/games" class="<?= str_starts_with($currentPath, '/admin/games') && ! str_contains($currentPath, 'dashboard') ? 'active' : '' ?>">Games</a>
            <?php if ($game): ?>
                <a href="/admin/games/<?= (int) $game['id'] ?>/dashboard" class="<?= str_contains($currentPath, '/dashboard') ? 'active' : '' ?>">Live dashboard</a>
                <a href="/admin/games/<?= (int) $game['id'] ?>/teams" class="<?= str_contains($currentPath, '/teams') ? 'active' : '' ?>">Teams</a>
                <a href="/admin/games/<?= (int) $game['id'] ?>/rooms" class="<?= str_contains($currentPath, '/rooms') ? 'active' : '' ?>">Kamers</a>
                <a href="/admin/games/<?= (int) $game['id'] ?>/answers" class="<?= str_contains($currentPath, '/answers') ? 'active' : '' ?>">Antwoorden beoordelen</a>
                <a href="/admin/games/<?= (int) $game['id'] ?>/chat" class="<?= str_contains($currentPath, '/chat') ? 'active' : '' ?>">Chat</a>
            <?php endif; ?>
            <a href="/admin/settings" class="<?= $currentPath === '/admin/settings' ? 'active' : '' ?>">Instellingen</a>
            <a href="/admin/audit-logs" class="<?= $currentPath === '/admin/audit-logs' ? 'active' : '' ?>">Audit log</a>
        </nav>
        <form method="POST" action="/admin/logout">
            <?= \App\Csrf::field() ?>
            <button type="submit" class="logout">Uitloggen (Beheerder)</button>
        </form>
    </div>
    <div class="admin-main">
        <?php if ($flash): ?><div class="flash"><?= View::e($flash) ?></div><?php endif; ?>
        <?= $content ?>
    </div>
</div>
<script src="<?= asset('/editor.js') ?>" defer></script>
</body>
</html>
