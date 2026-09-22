<?php

use App\Csrf;
use App\View;

/** @var array $teams */
$flash = View::pullFlash();
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Escape Room - Inloggen</title>
<link rel="stylesheet" href="<?= asset('/style.css') ?>">
</head>
<body>
<div class="center-screen">
    <div class="card text-center">
        <div class="big-emoji">🔐</div>
        <h1>ESCAPE ROOM</h1>
        <p class="subtitle">Log in met je teamnaam en teamcode</p>
        <?php if ($flash): ?><div class="flash"><?= View::e($flash) ?></div><?php endif; ?>
        <?php if ($teams === []): ?>
            <p style="color:#cbd5e1;">Er zijn nog geen teams aangemaakt. Vraag de organisator om teams toe te voegen.</p>
        <?php else: ?>
        <form method="POST" action="/login" style="text-align:left;">
            <?= Csrf::field() ?>
            <label for="team_id">Team</label>
            <select id="team_id" name="team_id" required>
                <option value="">Kies je team...</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= (int) $team['id'] ?>"><?= View::e($team['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="code">Teamcode</label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="4" placeholder="••••" required>
            <button type="submit" class="w-full">START</button>
        </form>
        <?php endif; ?>
        <p style="margin-top:1.5rem;font-size:.85rem;color:#cbd5e1;">Ben je organisator? <a href="/admin/login">Admin login</a></p>
    </div>
</div>
</body>
</html>
