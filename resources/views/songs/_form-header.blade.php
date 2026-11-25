{{--
    Riga in cima ai form "Nuovo brano" e "Modifica brano": il titolo della pagina a sinistra,
    i pulsanti Salva e Annulla a destra. Va messa dentro il <form>, così il pulsante Salva lo invia.
    Riceve: $title (titolo della pagina), $submitLabel (testo del pulsante Salva) e
    $cancelUrl (dove porta Annulla: l'elenco per un brano nuovo, l'anteprima per un brano in modifica;
    un indirizzo fisso, perché "la pagina precedente" dopo un errore di validazione sarebbe il form stesso).

    Se ci sono modifiche non salvate, Annulla non esce subito: resources/js/tab-editor.js apre la
    finestra di conferma qui sotto (API Popover di HTML, come la conferma dell'eliminazione).
--}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">{{ $title }}</h1>

    <div class="flex items-center gap-1">
        <button type="submit" title="{{ $submitLabel }}" aria-label="{{ $submitLabel }}"
                class="cursor-pointer rounded-md bg-gray-900 p-2 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
            @include('icons.save')
        </button>
        <a href="{{ $cancelUrl }}" id="form-cancel" title="Annulla" aria-label="Annulla"
           class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
            @include('icons.x-mark')
        </a>
    </div>
</div>

<div id="discard-changes" popover role="alertdialog" aria-labelledby="discard-changes-title"
     class="m-auto h-fit w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 text-left text-gray-900 shadow-xl backdrop:bg-black/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:backdrop:bg-black/60">
    <h2 id="discard-changes-title" class="mb-2 text-lg font-semibold">Uscire senza salvare?</h2>
    <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
        Le modifiche non salvate andranno perse.
    </p>

    <div class="flex justify-end gap-2">
        <button type="button" popovertarget="discard-changes" popovertargetaction="hide"
                title="Continua a modificare" aria-label="Continua a modificare"
                class="cursor-pointer rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
            @include('icons.pencil')
        </button>
        <a href="{{ $cancelUrl }}" title="Esci senza salvare" aria-label="Esci senza salvare"
           class="rounded-md bg-red-700 p-2 text-white hover:bg-red-800">
            @include('icons.x-mark')
        </a>
    </div>
</div>
