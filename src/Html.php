<?php

namespace App;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Maakt de HTML uit de editor veilig. Alles wat niet in de lijst hieronder
 * staat gaat eruit: geen scripts, geen event-handlers, geen externe bronnen.
 * De teams krijgen deze HTML ongefilterd te zien, dus hier wordt niets vertrouwd.
 */
class Html
{
    /** Toegestane tags met hun toegestane attributen. */
    private const ALLOWED = [
        'p' => ['style'],
        'br' => [],
        'strong' => [], 'b' => [],
        'em' => [], 'i' => [],
        'u' => [],
        'h2' => ['style'], 'h3' => ['style'],
        'ul' => [], 'ol' => [], 'li' => [],
        'div' => ['style'],
        'span' => ['style'],
        'img' => ['src', 'alt', 'style'],
        // Tabellen: nodig voor puzzels met roosters of kolommen naast elkaar.
        'table' => ['style'], 'thead' => [], 'tbody' => [], 'tr' => [],
        'td' => ['style', 'colspan'], 'th' => ['style', 'colspan'],
        'figure' => ['style'], 'figcaption' => ['style'],
    ];

    /** Tags waarvan ook de inhoud weg moet, niet alleen de tag zelf. */
    private const DROP_COMPLETELY = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta'];

    private const ALLOWED_STYLES = [
        'text-align', 'float', 'width', 'max-width', 'height', 'vertical-align',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'display', 'font-weight', 'font-style', 'text-decoration',
    ];

    /** Afbeeldingen mogen alleen uit onze eigen uploadmap komen. */
    private const IMAGE_PREFIX = '/storage/rooms/';

    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '' || trim(strip_tags($html)) === '' && ! str_contains($html, '<img')) {
            return null;
        }

        $doc = new DOMDocument;
        $vorige = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="wortel">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($vorige);

        $wortel = $doc->getElementById('wortel');
        if ($wortel === null) {
            return null;
        }

        self::schoonNode($wortel);

        $uit = '';
        foreach ($wortel->childNodes as $kind) {
            $uit .= $doc->saveHTML($kind);
        }

        $uit = trim($uit);

        return $uit === '' ? null : $uit;
    }

    /** Toont opgeslagen inhoud. Oudere kamers bevatten platte tekst zonder tags. */
    public static function render(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        if (! str_contains($value, '<')) {
            return nl2br(htmlspecialchars($value, ENT_QUOTES));
        }

        return (string) self::clean($value);
    }

    /** Korte, tagloze samenvatting voor overzichten in het beheer. */
    public static function toText(?string $value, int $max = 60): string
    {
        $tekst = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

        if ($tekst === '') {
            return '';
        }

        return mb_strlen($tekst) > $max ? mb_substr($tekst, 0, $max - 1).'…' : $tekst;
    }

    private static function schoonNode(DOMNode $node): void
    {
        // Achterstevoren: we verwijderen kinderen tijdens het lopen.
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $kind = $node->childNodes->item($i);

            if ($kind instanceof DOMElement) {
                self::schoonElement($kind);

                continue;
            }

            // Tekst mag blijven; commentaar en de rest gaat eruit.
            if ($kind->nodeType !== XML_TEXT_NODE) {
                $node->removeChild($kind);
            }
        }
    }

    private static function schoonElement(DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);

        if (in_array($tag, self::DROP_COMPLETELY, true)) {
            $element->parentNode->removeChild($element);

            return;
        }

        self::schoonNode($element);

        if (! isset(self::ALLOWED[$tag])) {
            self::pelUit($element);

            return;
        }

        foreach (iterator_to_array($element->attributes) as $attribuut) {
            $naam = strtolower($attribuut->nodeName);

            if (! in_array($naam, self::ALLOWED[$tag], true)) {
                $element->removeAttribute($attribuut->nodeName);

                continue;
            }

            if ($naam === 'style') {
                $schoon = self::schoonStijl($attribuut->nodeValue);
                $schoon === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $schoon);
            }

            if ($naam === 'src' && ! self::isEigenAfbeelding($attribuut->nodeValue)) {
                $element->parentNode->removeChild($element);

                return;
            }
        }

        if ($tag === 'img' && ! $element->hasAttribute('src')) {
            $element->parentNode->removeChild($element);
        }
    }

    /** Haalt de tag weg maar houdt de inhoud. */
    private static function pelUit(DOMElement $element): void
    {
        $ouder = $element->parentNode;

        while ($element->firstChild) {
            $ouder->insertBefore($element->firstChild, $element);
        }

        $ouder->removeChild($element);
    }

    private static function schoonStijl(string $stijl): string
    {
        $delen = [];

        foreach (explode(';', $stijl) as $regel) {
            if (! str_contains($regel, ':')) {
                continue;
            }

            [$eigenschap, $waarde] = array_map('trim', explode(':', $regel, 2));
            $eigenschap = strtolower($eigenschap);

            if (! in_array($eigenschap, self::ALLOWED_STYLES, true)) {
                continue;
            }

            // Geen url(), expression() of andere trucs: alleen simpele waarden.
            if (! preg_match('/^[a-z0-9 %.\-#]+$/i', $waarde)) {
                continue;
            }

            $delen[] = "$eigenschap: $waarde";
        }

        return implode('; ', $delen);
    }

    private static function isEigenAfbeelding(string $src): bool
    {
        return str_starts_with($src, self::IMAGE_PREFIX)
            && ! str_contains($src, '..')
            && preg_match('#^'.preg_quote(self::IMAGE_PREFIX, '#').'[A-Za-z0-9]+\.(jpg|png|gif|webp)$#', $src) === 1;
    }
}
