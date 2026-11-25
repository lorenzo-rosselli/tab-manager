/*
|--------------------------------------------------------------------------
| Editor del foglio della tabulatura (pagine "Nuovo brano" e "Modifica brano")
|--------------------------------------------------------------------------
| Usa Tiptap (https://tiptap.dev, licenza MIT), un editor di testo ricco.
| - La barra di formattazione applica font, dimensione, grassetto, corsivo, sottolineato,
|   colore ed evidenziatore alla parte di testo selezionata, e l'allineamento ai paragrafi selezionati.
| - Il documento (in JSON) viene copiato a ogni modifica nel campo nascosto "tab", che parte col form.
| - Righe automatiche in cima al foglio:
|     "Artista - Titolo"                       centrata, grassetto, 14 pt
|     "Strumento: Basso - Nome del gruppo"     centrata, corsivo, 8 pt
|        (solo le parti spuntate con "Mostra nel foglio"; senza spunte la riga non c'è)
|   Seguono i campi del form finché non vengono modificate a mano nel foglio: ogni riga automatica
|   ricorda il testo scritto in automatico (attributo autoText); se il testo nel foglio è diverso,
|   vuol dire che è stata modificata e non viene più toccata.
| - Testo incollato (dal foglio stesso, da Word o da una pagina web): colori ed evidenziatori vengono
|   riconosciuti e convertiti nella forma #RRGGBB; l'evidenziatore diventa il più vicino tra quelli di Word.
|   Anche font e dimensione vengono puliti ("10.0pt" → "10pt", virgolette tolte).
| - Le tavolozze dei colori si chiudono cliccando fuori o con Esc; con nessuna tavolozza aperta,
|   Esc porta all'elenco dei brani.
*/

import { Editor, Extension } from '@tiptap/core';
import { Fragment, Slice } from '@tiptap/pm/model';
import StarterKit from '@tiptap/starter-kit';
import { TextStyleKit } from '@tiptap/extension-text-style';
import TextAlign from '@tiptap/extension-text-align';
import Highlight from '@tiptap/extension-highlight';

const DEFAULT_FONT = 'Lucida Console';
const DEFAULT_SIZE = 10;

// Attributi in più sui paragrafi per riconoscere le righe automatiche.
// keepOnSplit: false → andando a capo dentro una riga automatica, il nuovo paragrafo è normale.
const AutoLine = Extension.create({
    name: 'autoLine',

    addGlobalAttributes() {
        return [{
            types: ['paragraph'],
            attributes: {
                auto: {
                    default: null,
                    keepOnSplit: false,
                    parseHTML: (element) => element.getAttribute('data-auto'),
                    renderHTML: (attributes) => (attributes.auto ? { 'data-auto': attributes.auto } : {}),
                },
                autoText: {
                    default: null,
                    keepOnSplit: false,
                    parseHTML: (element) => element.getAttribute('data-auto-text'),
                    renderHTML: (attributes) => (attributes.autoText ? { 'data-auto-text': attributes.autoText } : {}),
                },
            },
        }];
    },
});

/* ---------------- Colori incollati ---------------- */

// Un colore CSS qualsiasi ("#c00", "rgb(192, 0, 0)", "yellow"...) → [r, g, b], oppure null
// se non è un colore o è trasparente.
function toRgb(value) {
    let text = String(value ?? '').trim().toLowerCase();

    if (text === '' || text === 'transparent' || text === 'inherit' || text === 'initial') {
        return null;
    }

    // Nomi di colore ("yellow", "red"...): li traduce il browser.
    if (!text.startsWith('#') && !text.startsWith('rgb')) {
        try {
            const context = document.createElement('canvas').getContext('2d');
            if (!context) return null;
            context.fillStyle = '#010203';
            context.fillStyle = text;
            if (context.fillStyle === '#010203') return null; // non è un colore
            text = context.fillStyle;
        } catch {
            return null;
        }
    }

    let match = text.match(/^#([0-9a-f])([0-9a-f])([0-9a-f])$/);
    if (match) {
        return match.slice(1).map((digit) => parseInt(digit + digit, 16));
    }
    match = text.match(/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/);
    if (match) {
        return match.slice(1).map((pair) => parseInt(pair, 16));
    }
    match = text.match(/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)(?:[\s,/]+([\d.]+)(%?))?\s*\)$/);
    if (match) {
        if (match[4] !== undefined && parseFloat(match[4]) === 0) return null; // trasparente
        return match.slice(1, 4).map(Number);
    }

    return null;
}

const toHex = (rgb) => `#${rgb.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`.toUpperCase();
const distance = (a, b) => Math.hypot(a[0] - b[0], a[1] - b[1], a[2] - b[2]);

// Colore del testo: qualsiasi colore, ma quasi nero = "Automatico" e quasi bianco = ignorato
// (su un foglio bianco sarebbe invisibile: succede copiando da pagine con il tema scuro).
function textColor(value) {
    const rgb = toRgb(value);

    if (!rgb || Math.max(...rgb) <= 60 || Math.min(...rgb) >= 230) {
        return null;
    }

    return toHex(rgb);
}

// Evidenziatore: Word ammette solo la sua tavolozza, quindi si prende il colore più vicino.
// Sfondi bianchi o troppo lontani da ogni colore (es. lo sfondo di una pagina) vengono ignorati.
function highlightColor(value, palette) {
    const rgb = toRgb(value);

    if (!rgb || Math.min(...rgb) >= 240) {
        return null;
    }

    let best = null;
    palette.forEach((hex) => {
        const gap = distance(rgb, toRgb(hex));
        if (gap <= 150 && (!best || gap < best.gap)) best = { hex, gap };
    });

    return best?.hex ?? null;
}

const element = document.getElementById('tab-editor');

if (element) {
    const form = element.closest('form');
    const input = document.getElementById('tab');
    const toolbar = document.getElementById('tab-toolbar');
    // Colori dell'evidenziatore ammessi: quelli della tavolozza nella barra.
    const highlightPalette = [...toolbar.querySelectorAll('[data-palette="highlight"]')]
        .map((button) => button.dataset.value)
        .filter(Boolean);

    // Evidenziatore che riconosce, oltre a <mark>, anche gli sfondi colorati del testo incollato
    // (Word usa "background" e "mso-highlight", le pagine web "background-color").
    const PastedHighlight = Highlight.extend({
        parseHTML() {
            const fromStyle = (value) => {
                // "background" può contenere più valori (es. "yellow none repeat"): basta il primo colore.
                const color = value.split(/\s+(?![^(]*\))/).map((part) => highlightColor(part, highlightPalette)).find(Boolean);
                return color ? { color } : false;
            };

            return [
                ...this.parent(),
                { style: 'background-color', getAttrs: fromStyle },
                { style: 'background', getAttrs: fromStyle },
                { style: 'mso-highlight', getAttrs: fromStyle },
            ];
        },
    });

    // Font incollato: '"Courier New", monospace' → 'Courier New' (il primo, senza virgolette).
    const cleanFont = (value) => String(value ?? '').split(',')[0].replace(/['"]+/g, '').trim() || null;

    // Dimensione incollata: "10.0pt" → "10pt", "16px" → "12pt" (1 px = 0,75 pt).
    function cleanSize(value) {
        const match = String(value ?? '').trim().match(/^([\d.]+)\s*(pt|px)$/i);
        if (!match) return null;
        const points = parseFloat(match[1]) * (match[2].toLowerCase() === 'px' ? 0.75 : 1);

        return points > 0 ? `${Math.round(points)}pt` : null;
    }

    // Sistema la formattazione dei pezzi di testo incollati: colore del testo in #RRGGBB,
    // evidenziatore nel colore più vicino della tavolozza di Word, font e dimensione puliti.
    function cleanPasted(fragment) {
        const nodes = [];

        fragment.forEach((node) => {
            if (!node.isText) {
                nodes.push(node.copy(cleanPasted(node.content)));

                return;
            }

            const marks = node.marks.flatMap((mark) => {
                if (mark.type.name === 'textStyle') {
                    return [mark.type.create({
                        ...mark.attrs,
                        color: textColor(mark.attrs.color),
                        fontFamily: cleanFont(mark.attrs.fontFamily),
                        fontSize: cleanSize(mark.attrs.fontSize),
                    })];
                }
                if (mark.type.name === 'highlight') {
                    const color = highlightColor(mark.attrs.color, highlightPalette);

                    return color ? [mark.type.create({ color })] : [];
                }

                return [mark];
            });

            nodes.push(node.mark(marks));
        });

        return Fragment.fromArray(nodes);
    }

    const fields = {
        title: form.elements.title,
        artist: form.elements.artist,
        band: form.elements.band,
        instrument: form.elements.instrument,
        showInstrument: document.getElementById('show_instrument'),
        showBand: document.getElementById('show_band'),
    };

    const editor = new Editor({
        element,
        extensions: [
            // Solo ciò che serve: paragrafi, a capo, grassetto, corsivo, sottolineato, annulla/ripeti.
            StarterKit.configure({
                blockquote: false,
                bulletList: false,
                code: false,
                codeBlock: false,
                heading: false,
                horizontalRule: false,
                link: false,
                listItem: false,
                listKeymap: false,
                orderedList: false,
                strike: false,
                trailingNode: false,
            }),
            TextStyleKit.configure({ backgroundColor: false, lineHeight: false }), // font, dimensione, colore
            PastedHighlight.configure({ multicolor: true }),                        // evidenziatore a più colori
            TextAlign.configure({ types: ['paragraph'], alignments: ['left', 'center', 'right'] }),
            AutoLine,
        ],
        content: JSON.parse(input.value),
        // Niente scorciatoie "markdown" (es. *testo* → corsivo): in una tabulatura * e _ sono testo.
        enableInputRules: false,
        enablePasteRules: false,
        editorProps: {
            attributes: { spellcheck: 'false', 'aria-label': 'Tabulatura', 'aria-multiline': 'true' },
            // Testo incollato: ogni riga diventa un paragrafo, comprese le righe vuote
            // (il comportamento normale le fonderebbe).
            clipboardTextParser(text, $context, plain, view) {
                const { schema } = view.state;
                const paragraphs = text.split(/\r\n?|\n/).map((line) => (
                    schema.nodes.paragraph.create(null, line ? schema.text(line) : null)
                ));

                return new Slice(Fragment.from(paragraphs), 1, 1);
            },
            // Testo incollato con formattazione: colori, evidenziatori, font e dimensioni riconosciuti e convertiti.
            transformPasted(slice) {
                return new Slice(cleanPasted(slice.content), slice.openStart, slice.openEnd);
            },
        },
        onUpdate: ({ editor }) => {
            input.value = JSON.stringify(editor.getJSON());
        },
        onTransaction: () => refreshToolbar(),
    });

    /* ---------------- Barra di formattazione ---------------- */

    const fontSelect = document.getElementById('tab-font');
    const sizeSelect = document.getElementById('tab-size');

    fontSelect.addEventListener('change', () => editor.chain().focus().setFontFamily(fontSelect.value).run());
    sizeSelect.addEventListener('change', () => editor.chain().focus().setFontSize(`${sizeSelect.value}pt`).run());

    // Tavolozze di colore ed evidenziatore: menu a tendina <details>; scelto il colore, il menu si chiude.
    // data-value vuoto = "Automatico" (testo nero) / "Nessuno" (niente evidenziatore).
    toolbar.querySelectorAll('[data-palette]').forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            const { palette, value } = button.dataset;
            const chain = editor.chain().focus();

            if (palette === 'color') {
                (value ? chain.setColor(value) : chain.unsetColor()).run();
            } else {
                (value ? chain.setHighlight({ color: value }) : chain.unsetHighlight()).run();
            }

            button.closest('details').open = false;
        });
    });

    // Le tavolozze si chiudono cliccando in qualsiasi altro punto della pagina
    // (anche aprendo l'altra tavolozza: così ne resta aperta una sola).
    const palettes = [...toolbar.querySelectorAll('details')];

    document.addEventListener('click', (event) => {
        palettes.forEach((details) => {
            if (details.open && !details.contains(event.target)) details.open = false;
        });
    });

    // Esc: chiude la tavolozza aperta; se non ce ne sono, torna all'elenco dei brani.
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || event.defaultPrevented || event.isComposing) {
            return;
        }

        const open = palettes.find((details) => details.open);

        if (open) {
            open.open = false;
            open.querySelector('summary').focus();
        } else {
            window.location.href = element.dataset.home;
        }
    });

    toolbar.querySelectorAll('[data-command]').forEach((button) => {
        // mousedown: evita che il clic tolga la selezione al foglio prima del comando.
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            const chain = editor.chain().focus();
            const { command, value } = button.dataset;

            if (command === 'align') {
                chain.setTextAlign(value).run();
            } else {
                chain[command]().run(); // toggleBold, toggleItalic, toggleUnderline
            }
        });
    });

    // Mostra nella barra la formattazione del punto in cui si trova il cursore.
    function refreshToolbar() {
        const style = editor.getAttributes('textStyle');

        fontSelect.value = style.fontFamily || DEFAULT_FONT;
        sizeSelect.value = parseInt(style.fontSize, 10) || DEFAULT_SIZE;

        // Le barrette sotto "A" e sotto l'evidenziatore mostrano il colore attuale.
        document.getElementById('tab-color-bar').style.backgroundColor = style.color || '#000000';
        document.getElementById('tab-highlight-bar').style.backgroundColor = editor.getAttributes('highlight').color || 'transparent';

        toolbar.querySelectorAll('[data-command]').forEach((button) => {
            const { command, value } = button.dataset;
            const active = command === 'align'
                ? (editor.getAttributes('paragraph').textAlign || 'left') === value
                : editor.isActive(button.dataset.mark);

            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    /* ---------------- Righe automatiche ---------------- */

    const ORDER = ['header', 'info'];

    // Testo che ogni riga automatica dovrebbe avere ora ('' = la riga non deve esserci).
    function expectedTexts() {
        const instrumentLabel = fields.instrument.options[fields.instrument.selectedIndex]?.text.trim() ?? '';

        return {
            header: [fields.artist.value.trim(), fields.title.value.trim()].filter(Boolean).join(' - '),
            // "Strumento: Basso - Nome del gruppo" (stessa regola di TabDocument::infoLine)
            info: [
                fields.showInstrument.checked ? `Strumento: ${instrumentLabel}` : '',
                fields.showBand.checked ? fields.band.value.trim() : '',
            ].filter(Boolean).join(' - '),
        };
    }

    // Cerca una riga automatica tra i paragrafi del documento: restituisce nodo e posizione.
    function findAuto(doc, kind) {
        let found = null;

        doc.forEach((node, offset) => {
            if (!found && node.attrs.auto === kind) {
                found = { node, pos: offset };
            }
        });

        return found;
    }

    function autoParagraph(kind, text) {
        const { schema } = editor.state;
        const header = kind === 'header';
        const marks = [
            header ? schema.marks.bold.create() : schema.marks.italic.create(),
            schema.marks.textStyle.create({ fontSize: header ? '14pt' : '8pt' }),
        ];

        return schema.nodes.paragraph.create(
            { textAlign: 'center', auto: kind, autoText: text },
            schema.text(text, marks),
        );
    }

    function syncAutoLines() {
        const expected = expectedTexts();
        const tr = editor.state.tr;

        ORDER.forEach((kind, index) => {
            const text = expected[kind];
            const existing = findAuto(tr.doc, kind);

            if (existing) {
                const untouched = existing.node.textContent === existing.node.attrs.autoText;

                if (!untouched) {
                    return; // modificata a mano: resta com'è
                }
                if (text === '') {
                    tr.delete(existing.pos, existing.pos + existing.node.nodeSize);
                } else if (text !== existing.node.attrs.autoText) {
                    tr.replaceWith(existing.pos, existing.pos + existing.node.nodeSize, autoParagraph(kind, text));
                }

                return;
            }

            if (text === '') {
                return;
            }

            // Posizione: subito dopo la riga automatica che la precede (titolo → strumento/gruppo).
            let pos = 0;
            ORDER.slice(0, index).forEach((previous) => {
                tr.doc.forEach((node, offset) => {
                    if (node.attrs.auto === previous) pos = offset + node.nodeSize;
                });
            });

            tr.insert(pos, autoParagraph(kind, text));
        });

        if (tr.docChanged) {
            editor.view.dispatch(tr);
        }
    }

    ['title', 'artist', 'band'].forEach((name) => fields[name].addEventListener('input', syncAutoLines));
    [fields.instrument, fields.showInstrument, fields.showBand].forEach((field) => field.addEventListener('change', syncAutoLines));

    refreshToolbar();
}
