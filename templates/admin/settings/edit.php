<?php

use App\Csrf;
use App\View;

/** @var array $settings */
?>

<h1>Instellingen</h1>
<p style="color:#475569;margin-top:0;">Deze instellingen gelden voor alle teams en worden op het spelscherm getoond.</p>

<div class="panel" style="max-width:32rem;">
    <form method="POST" action="/admin/settings">
        <?= Csrf::field() ?>

        <label for="support_phone">Telefoonnummer voor vragen</label>
        <input id="support_phone" name="support_phone" type="text" value="<?= View::e($settings['support_phone'] ?? '') ?>" placeholder="06 12 34 56 78">
        <p style="font-size:.75rem;color:#475569;margin:-.75rem 0 1rem;">Wordt altijd zichtbaar getoond op het spelscherm van teams, zodat ze op elk moment kunnen bellen.</p>

        <label for="support_note">Extra tekst bij de contactgegevens (optioneel)</label>
        <textarea id="support_note" name="support_note" rows="2" placeholder="Bijv. 'Bereikbaar tijdens het hele evenement'"><?= View::e($settings['support_note'] ?? '') ?></textarea>

        <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;">
            <input type="hidden" name="chat_enabled" value="0">
            <input type="checkbox" name="chat_enabled" value="1" <?= ($settings['chat_enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="width:auto;margin:0;">
            Chat met de organisatie inschakelen
        </label>
        <p style="font-size:.75rem;color:#475569;margin:-.5rem 0 1rem;">Als dit uit staat, kunnen teams alleen nog bellen.</p>

        <button type="submit">Opslaan</button>
    </form>
</div>
