/**
 * Kleine tekstverwerker voor de opdrachten. Geen bibliotheek van buiten: dit
 * draait op een schoolavond zonder internet, dus alles staat hier lokaal.
 *
 * De inhoud gaat als HTML naar een verborgen veld; de server filtert die HTML
 * nog een keer (zie src/Html.php) voordat teams hem te zien krijgen.
 */
(() => {
    const KNOPPEN = [
        { cmd: 'bold', label: 'B', titel: 'Vet', stijl: 'font-weight:800;' },
        { cmd: 'italic', label: 'I', titel: 'Cursief', stijl: 'font-style:italic;' },
        { cmd: 'underline', label: 'U', titel: 'Onderstreept', stijl: 'text-decoration:underline;' },
        { blok: 'h2', label: 'Kop', titel: 'Kop' },
        { blok: 'h3', label: 'Subkop', titel: 'Subkop' },
        { blok: 'p', label: 'Tekst', titel: 'Gewone tekst' },
        { cmd: 'insertUnorderedList', label: '• Lijst', titel: 'Opsomming' },
        { cmd: 'insertOrderedList', label: '1. Lijst', titel: 'Genummerde lijst' },
        { cmd: 'justifyLeft', label: '⯇', titel: 'Links uitlijnen' },
        { cmd: 'justifyCenter', label: '≡', titel: 'Midden uitlijnen' },
        { cmd: 'justifyRight', label: '⯈', titel: 'Rechts uitlijnen' },
        { cmd: 'removeFormat', label: 'Wis opmaak', titel: 'Opmaak wissen' },
    ];

    // Hoe een afbeelding tussen de tekst staat. "Zwevend" laat de tekst ernaast lopen.
    const PLAATSINGEN = {
        links: 'float: left; width: 45%; margin: 0 1rem 1rem 0;',
        rechts: 'float: right; width: 45%; margin: 0 0 1rem 1rem;',
        midden: 'float: none; display: block; width: 70%; margin: 1rem auto;',
        vol: 'float: none; display: block; width: 100%; margin: 1rem 0;',
    };

    const BREEDTES = { Klein: 25, Normaal: 45, Groot: 70, Vol: 100 };

    function maakKnop(label, titel, extraStijl = '') {
        const knop = document.createElement('button');
        knop.type = 'button';
        knop.className = 'editor-knop';
        knop.title = titel;
        knop.textContent = label;
        if (extraStijl) knop.setAttribute('style', extraStijl);
        // mousedown voorkomen: anders verliest de tekst zijn selectie bij het klikken.
        knop.addEventListener('mousedown', (e) => e.preventDefault());
        return knop;
    }

    function stijlVan(img) {
        return img.getAttribute('style') || '';
    }

    function zetBreedte(img, procent) {
        const rest = stijlVan(img).replace(/width:[^;]*;?/gi, '').trim();
        img.setAttribute('style', `${rest} width: ${procent}%;`.trim());
    }

    function start(houder) {
        const veld = houder.querySelector('input[type=hidden]');
        const balk = document.createElement('div');
        balk.className = 'editor-bar';

        const vlak = document.createElement('div');
        vlak.className = 'editor-area';
        vlak.contentEditable = 'true';
        vlak.innerHTML = veld.value || '';

        // --- tekstknoppen ---
        KNOPPEN.forEach((k) => {
            const knop = maakKnop(k.label, k.titel, k.stijl || '');
            knop.addEventListener('click', () => {
                vlak.focus();
                if (k.blok) {
                    document.execCommand('formatBlock', false, k.blok);
                } else {
                    document.execCommand(k.cmd, false, null);
                }
                bijwerken();
            });
            balk.append(knop);
        });

        // --- afbeelding invoegen ---
        const kiezer = document.createElement('input');
        kiezer.type = 'file';
        kiezer.accept = 'image/jpeg,image/png,image/gif,image/webp';
        kiezer.hidden = true;

        const afbKnop = maakKnop('🖼 Afbeelding', 'Afbeelding invoegen');
        afbKnop.addEventListener('click', () => kiezer.click());
        balk.append(afbKnop, kiezer);

        kiezer.addEventListener('change', async () => {
            const bestand = kiezer.files[0];
            if (!bestand) return;

            afbKnop.disabled = true;
            afbKnop.textContent = 'Bezig…';

            try {
                const data = new FormData();
                data.append('image', bestand);
                data.append('_token', houder.dataset.token);

                const antwoord = await fetch(houder.dataset.uploadUrl, { method: 'POST', body: data });
                const uitslag = await antwoord.json();

                if (!antwoord.ok) throw new Error(uitslag.error || 'Uploaden mislukt.');

                vlak.focus();
                document.execCommand(
                    'insertHTML',
                    false,
                    `<p><img src="${uitslag.url}" alt="" style="${PLAATSINGEN.midden}"></p><p><br></p>`
                );
                bijwerken();
            } catch (fout) {
                alert(fout.message);
            } finally {
                kiezer.value = '';
                afbKnop.disabled = false;
                afbKnop.textContent = '🖼 Afbeelding';
            }
        });

        // --- tweede balk: verschijnt zodra je een afbeelding aanklikt ---
        const afbBalk = document.createElement('div');
        afbBalk.className = 'editor-bar editor-bar-image';
        afbBalk.hidden = true;

        const uitleg = document.createElement('span');
        uitleg.className = 'editor-hint';
        uitleg.textContent = 'Afbeelding:';
        afbBalk.append(uitleg);

        let gekozen = null;

        Object.entries({ links: 'Tekst rechts ernaast', rechts: 'Tekst links ernaast', midden: 'Op zichzelf', vol: 'Volle breedte' })
            .forEach(([sleutel, titel]) => {
                const knop = maakKnop(titel, titel);
                knop.addEventListener('click', () => {
                    if (!gekozen) return;
                    gekozen.setAttribute('style', PLAATSINGEN[sleutel]);
                    bijwerken();
                });
                afbBalk.append(knop);
            });

        Object.entries(BREEDTES).forEach(([label, procent]) => {
            const knop = maakKnop(label, `Breedte ${procent}%`);
            knop.addEventListener('click', () => {
                if (!gekozen) return;
                zetBreedte(gekozen, procent);
                bijwerken();
            });
            afbBalk.append(knop);
        });

        const wegKnop = maakKnop('Verwijderen', 'Afbeelding verwijderen');
        wegKnop.addEventListener('click', () => {
            if (!gekozen) return;
            gekozen.remove();
            kiesAfbeelding(null);
            bijwerken();
        });
        afbBalk.append(wegKnop);

        function kiesAfbeelding(img) {
            if (gekozen) gekozen.classList.remove('is-gekozen');
            gekozen = img;
            if (gekozen) gekozen.classList.add('is-gekozen');
            afbBalk.hidden = gekozen === null;
        }

        vlak.addEventListener('click', (e) => {
            kiesAfbeelding(e.target.tagName === 'IMG' ? e.target : null);
        });

        function bijwerken() {
            // De hulpklasse hoort niet in de opgeslagen inhoud.
            const kopie = vlak.cloneNode(true);
            kopie.querySelectorAll('.is-gekozen').forEach((el) => el.classList.remove('is-gekozen'));
            kopie.querySelectorAll('[class=""]').forEach((el) => el.removeAttribute('class'));
            veld.value = kopie.innerHTML;
        }

        vlak.addEventListener('input', bijwerken);
        vlak.addEventListener('blur', bijwerken);
        houder.closest('form').addEventListener('submit', bijwerken);

        houder.prepend(balk, afbBalk, vlak);
        bijwerken();
    }

    document.querySelectorAll('[data-editor]').forEach(start);
})();
