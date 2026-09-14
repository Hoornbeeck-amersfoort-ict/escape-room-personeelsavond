<?php

use App\View;

/** @var array|null $room */
$alternatives = $room && $room['alternative_answers'] ? implode("\n", json_decode($room['alternative_answers'], true)) : '';
?>
<label for="name">Naam</label>
<input id="name" name="name" type="text" value="<?= View::e($room['name'] ?? '') ?>" required>

<label for="description">Korte omschrijving</label>
<textarea id="description" name="description" rows="2"><?= View::e($room['description'] ?? '') ?></textarea>

<label for="instructions">Opdracht / instructies</label>
<textarea id="instructions" name="instructions" rows="5"><?= View::e($room['instructions'] ?? '') ?></textarea>

<label for="answer">Correct antwoord</label>
<input id="answer" name="answer" type="text" value="<?= View::e($room['answer'] ?? '') ?>" required>

<label for="alternative_answers">Alternatieve antwoorden (één per regel)</label>
<textarea id="alternative_answers" name="alternative_answers" rows="3"><?= View::e($alternatives) ?></textarea>
<p style="font-size:.75rem;color:#64748b;margin:-.5rem 0 1rem;">Antwoorden worden niet hoofdlettergevoelig vergeleken en spaties aan begin/eind tellen niet mee.</p>

<label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" <?= ($room['active'] ?? true) ? 'checked' : '' ?> style="width:auto;margin:0;">
    Actief (kamer kan toegewezen worden)
</label>
