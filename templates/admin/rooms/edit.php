<?php

use App\Csrf;
use App\View;

/** @var array $game */
/** @var array $room */
?>
<h1>Kamer bewerken — <?= View::e($game['name']) ?></h1>
<div class="panel" style="max-width:40rem;">
    <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/rooms/<?= (int) $room['id'] ?>">
        <?= Csrf::field() ?>
        <?php require __DIR__.'/_form.php'; ?>
        <button type="submit" class="w-full" style="margin-top:1rem;">Opslaan</button>
    </form>
</div>
