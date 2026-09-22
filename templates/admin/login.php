<?php

use App\Csrf;
use App\View;

$flash = View::pullFlash();
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login — Escape Room</title>
<link rel="stylesheet" href="<?= asset('/style.css') ?>">
</head>
<body>
<div class="center-screen">
    <div class="card">
        <h1>Admin login</h1>
        <p class="subtitle">Escape room beheeromgeving</p>
        <?php if ($flash): ?><div class="flash"><?= View::e($flash) ?></div><?php endif; ?>
        <form method="POST" action="/admin/login">
            <?= Csrf::field() ?>
            <label for="email">E-mailadres</label>
            <input id="email" name="email" type="email" required autofocus>
            <label for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" required>
            <button type="submit" class="w-full">Inloggen</button>
        </form>
        <a href="/" class="btn secondary w-full" style="display:block;margin-top:.6rem;">← Terug</a>
    </div>
</div>
</body>
</html>
