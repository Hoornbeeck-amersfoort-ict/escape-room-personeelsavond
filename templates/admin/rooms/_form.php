<?php

use App\Csrf;
use App\Html;
use App\View;

/** @var array|null $room */
/** @var array $game */
$alternatives = $room && $room['alternative_answers'] ? implode("\n", json_decode($room['alternative_answers'], true)) : '';
$uploadUrl = '/admin/games/'.(int) $game['id'].'/rooms/upload-image';

/** Eén editor: verborgen veld met de HTML, de knoppenbalk komt uit editor.js. */
$editor = function (string $naam, ?string $waarde) use ($uploadUrl) {
    $id = 'editor-'.$naam;
    echo '<div class="editor" data-editor data-upload-url="'.View::e($uploadUrl).'" data-token="'.View::e(Csrf::token()).'">';
    echo '<input type="hidden" id="'.$id.'" name="'.View::e($naam).'" value="'.View::e(Html::render($waarde)).'">';
    echo '</div>';
};
?>
<label for="name">Naam</label>
<input id="name" name="name" type="text" value="<?= View::e($room['name'] ?? '') ?>" required>

<label for="description">Korte omschrijving</label>
<textarea id="description" name="description" rows="2"><?= View::e($room['description'] ?? '') ?></textarea>

<label>Onderweg naar de kamer</label>
<p style="font-size:.75rem;color:#475569;margin:-.2rem 0 .5rem;">Staat op het scherm "Jullie volgende kamer", vóór het team de kamer start. Laat leeg als er onderweg niets te doen is.</p>
<?php $editor('route_instructions', $room['route_instructions'] ?? null); ?>

<label>Opdracht in de kamer</label>
<p style="font-size:.75rem;color:#475569;margin:-.2rem 0 .5rem;">Dit ziet het team nadat het de kamer gestart heeft.</p>
<?php $editor('instructions', $room['instructions'] ?? null); ?>

<label for="answer">Correct antwoord</label>
<input id="answer" name="answer" type="text" value="<?= View::e($room['answer'] ?? '') ?>" required>

<label for="alternative_answers">Alternatieve antwoorden (één per regel)</label>
<textarea id="alternative_answers" name="alternative_answers" rows="3"><?= View::e($alternatives) ?></textarea>
<p style="font-size:.75rem;color:#475569;margin:-.5rem 0 1rem;">Antwoorden worden niet hoofdlettergevoelig vergeleken en spaties aan begin/eind tellen niet mee.</p>

<label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;">
    <input type="hidden" name="exclusive" value="0">
    <input type="checkbox" name="exclusive" value="1" <?= ($room['exclusive'] ?? false) ? 'checked' : '' ?> style="width:auto;margin:0;">
    Maar één team tegelijk in deze kamer
</label>
<p style="font-size:.75rem;color:#475569;margin:-.5rem 0 1rem;">Is de kamer bezet, dan krijgen andere teams eerst een andere kamer — of een wachtscherm als dit hun laatste kamer is.</p>

<label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" <?= ($room['active'] ?? true) ? 'checked' : '' ?> style="width:auto;margin:0;">
    Actief (kamer kan toegewezen worden)
</label>

<label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;margin-top:.5rem;">
    <input type="hidden" name="allow_image_answer" value="0">
    <input type="checkbox" name="allow_image_answer" value="1" <?= ($room['allow_image_answer'] ?? false) ? 'checked' : '' ?> style="width:auto;margin:0;">
    Foto-antwoord toestaan
</label>
<p style="font-size:.75rem;color:#475569;margin:-.2rem 0 0;">Naast (of in plaats van) een tekstantwoord mag het team een foto insturen, bijvoorbeeld van een gebouwde constructie. Een foto wordt altijd handmatig beoordeeld op de pagina "Antwoorden beoordelen".</p>
