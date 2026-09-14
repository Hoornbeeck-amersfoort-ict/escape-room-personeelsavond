<?php

use App\Csrf;
?>
<h1>Nieuw game</h1>
<div class="panel" style="max-width:32rem;">
    <form method="POST" action="/admin/games">
        <?= Csrf::field() ?>
        <label for="name">Naam</label>
        <input id="name" name="name" type="text" required autofocus>
        <button type="submit" class="w-full">Aanmaken</button>
    </form>
</div>
