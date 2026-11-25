/*
|--------------------------------------------------------------------------
| Pagine A4 del foglio (editor nel form e anteprima del brano)
|--------------------------------------------------------------------------
| Il foglio è un unico testo continuo, ma viene mostrato su pagine A4 separate, come in Word:
| - pagina 21 × 29,7 cm, margini di 2 cm → zona scrivibile alta 25,7 cm;
| - tra una pagina e l'altra una striscia grigia di 1 cm.
|
| Come funziona:
| 1. si misura l'altezza di ogni blocco del foglio (paragrafo o interruzione di pagina);
| 2. paginate() calcola dove un paragrafo non sta più nella pagina: lì serve uno "spazio" che lo
|    spinga all'inizio della pagina successiva (un paragrafo non viene mai spezzato a metà);
|    dopo un'interruzione di pagina il blocco successivo va sempre su una pagina nuova;
| 3. drawPages() disegna dietro al testo le pagine bianche, con il bordo tratteggiato dei margini.
|
| Nell'editor gli spazi sono "decorazioni" di Tiptap (resources/js/tab-editor.js): si vedono ma non fanno
| parte del documento, quindi non finiscono né nel JSON né nel Word. Nell'anteprima (elemento con
| data-paginate) sono semplici <div> inseriti da questo file.
| La posizione dei salti pagina è molto vicina a quella di Word, ma non garantita al millimetro:
| browser e Word misurano le righe in modo leggermente diverso.
*/

const CM = 96 / 2.54; // pixel CSS in un centimetro

export const PAGE_HEIGHT = 29.7 * CM;
export const MARGIN = 2 * CM;
export const GAP = 1 * CM;
export const CONTENT_HEIGHT = PAGE_HEIGHT - 2 * MARGIN;

// Inizio della zona scrivibile della pagina n (0 = prima pagina), misurato dall'alto del foglio.
const contentTop = (page) => page * (PAGE_HEIGHT + GAP) + MARGIN;
const contentBottom = (page) => contentTop(page) + CONTENT_HEIGHT;

/**
 * blocks: [{ height, pageBreak }] nell'ordine del foglio.
 * Restituisce gli spazi da inserire ({ index: blocco davanti a cui va lo spazio, height }) e il numero di pagine.
 */
export function paginate(blocks) {
    const breaks = [];
    let page = 0;
    let pos = contentTop(0);  // dove inizia il prossimo blocco
    let forced = false;       // il blocco precedente era un'interruzione di pagina

    blocks.forEach((block, index) => {
        const tooLong = pos + block.height > contentBottom(page) + 0.5; // 0,5 px di tolleranza per gli arrotondamenti
        const atTop = pos <= contentTop(page) + 0.5;                    // un paragrafo più alto di una pagina non si sposta
        const inGap = pos < contentTop(page) - 0.5;                     // dopo un paragrafo finito tra due pagine

        if (forced || inGap || (tooLong && !atTop)) {
            let next = inGap ? page : page + 1;
            while (contentTop(next) < pos) next++; // dopo un paragrafo più alto di una pagina

            breaks.push({ index, height: contentTop(next) - pos });
            pos = contentTop(next);
            page = next;
        }

        pos += block.height;
        forced = block.pageBreak;
        while (pos > contentBottom(page) + 0.5) page++; // paragrafo che sborda sulla pagina dopo
    });

    return { breaks, pages: page + 1 };
}

/** Altezza totale del foglio con n pagine. */
export const paperHeight = (pages) => pages * (PAGE_HEIGHT + GAP) - GAP;

/** Disegna n pagine bianche nel livello di sfondo (solo se il numero è cambiato). */
export function drawPages(layer, pages) {
    layer.parentElement.classList.add('is-paged'); // da qui lo sfondo bianco lo danno le pagine

    if (layer.childElementCount === pages) {
        return;
    }

    layer.replaceChildren(...Array.from({ length: pages }, (_, page) => {
        const sheet = document.createElement('div');
        sheet.className = 'tab-page';
        sheet.style.top = `${page * (PAGE_HEIGHT + GAP)}px`;

        return sheet;
    }));
}

/** Elemento "spazio" alto height pixel. */
export function spacer(height) {
    const element = document.createElement('div');
    element.className = 'tab-page-spacer';
    element.contentEditable = 'false';
    element.style.height = `${height}px`;

    return element;
}

/* ---------------- Anteprima del brano (pagina show) ---------------- */

function paginatePreview(paper) {
    const sheet = paper.querySelector('.tab-sheet');
    const layer = paper.querySelector('.tab-paper-pages');

    sheet.querySelectorAll(':scope > .tab-page-spacer').forEach((element) => element.remove());

    const elements = [...sheet.children];
    const { breaks, pages } = paginate(elements.map((element) => ({
        height: element.getBoundingClientRect().height,
        pageBreak: element.classList.contains('tab-page-break'),
    })));

    breaks.forEach(({ index, height }) => elements[index].before(spacer(height)));
    drawPages(layer, pages);
    paper.style.height = `${paperHeight(pages)}px`;
}

document.querySelectorAll('[data-paginate]').forEach((paper) => {
    paginatePreview(paper);
    // I font possono arrivare dopo il primo disegno: si rimisura quando sono pronti.
    document.fonts?.ready.then(() => paginatePreview(paper));
});
