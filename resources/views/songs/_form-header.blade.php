{{--
    Riga in cima ai form "Nuovo brano" e "Modifica brano": il titolo della pagina a sinistra,
    i pulsanti Salva e Annulla a destra. Va messa dentro il <form>, così il pulsante Salva lo invia.
    Riceve: $title (titolo della pagina) e $submitLabel (testo del pulsante Salva).
--}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">{{ $title }}</h1>

    <div class="flex items-center gap-1">
        <button type="submit" title="{{ $submitLabel }}" aria-label="{{ $submitLabel }}"
                class="cursor-pointer rounded-md bg-gray-900 p-2 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
            @include('icons.save')
        </button>
        <a href="{{ url()->previous() }}" title="Annulla" aria-label="Annulla"
           class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
            @include('icons.x-mark')
        </a>
    </div>
</div>
