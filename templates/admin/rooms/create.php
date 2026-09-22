<?php

use App\Csrf;
use App\View;

/** @var array $game */
?>
<h1>Nieuwe kamer — <?= View::e($game['name']) ?></h1>
<div class="panel" style="max-width:40rem;">
    <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/rooms" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <?php $room = null; require __DIR__.'/_form.php'; ?>
        <button type="submit" class="w-full" style="margin-top:1rem;">Aanmaken</button>
    </form>
</div>
