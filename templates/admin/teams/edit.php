<?php

use App\Csrf;
use App\View;

/** @var array $game */
/** @var array $team */
?>
<h1>Team bewerken</h1>
<div class="panel" style="max-width:32rem;">
    <form method="POST" action="/admin/games/<?= (int) $game['id'] ?>/teams/<?= (int) $team['id'] ?>">
        <?= Csrf::field() ?>
        <label for="name">Teamnaam</label>
        <input id="name" name="name" type="text" value="<?= View::e($team['name']) ?>" required>
        <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;margin-bottom:1rem;">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" <?= $team['active'] ? 'checked' : '' ?> style="width:auto;margin:0;">
            Actief (team mag inloggen en spelen)
        </label>
        <button type="submit" class="w-full">Opslaan</button>
    </form>
</div>
