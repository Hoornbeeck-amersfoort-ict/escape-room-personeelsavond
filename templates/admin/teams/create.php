<?php

use App\Csrf;

/** @var array $game */
?>
<h1>Nieuw team — <?= \App\View::e($game['name']) ?></h1>
<div class="panel" style="max-width:32rem;">
    <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/teams">
        <?= Csrf::field() ?>
        <label for="name">Teamnaam</label>
        <input id="name" name="name" type="text" required autofocus>
        <label for="code">Teamcode (4 cijfers)</label>
        <input id="code" name="code" type="text" inputmode="numeric" maxlength="4" required>
        <button type="submit" class="w-full">Aanmaken</button>
    </form>
</div>
