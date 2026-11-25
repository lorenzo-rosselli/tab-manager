{{--
    Pulsante "Elimina" + finestra di conferma (modale), senza JavaScript.
    Usa l'API Popover di HTML:
    - il pulsante con popovertarget="..." apre il pannello con quell'id;
    - il browser lo mostra in primo piano sopra uno sfondo scuro (::backdrop);
    - "m-auto h-fit" lo centra: il reset CSS di Tailwind azzera i margini, quindi va rimesso margin: auto;
    - si chiude con "Annulla", con il tasto Esc o cliccando fuori.
    Riceve: $song
--}}
@php($dialogId = 'delete-song-'.$song->id)

<button type="button" popovertarget="{{ $dialogId }}" title="Elimina" aria-label="Elimina"
        class="cursor-pointer rounded-md p-2 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300">
    @include('icons.trash')
</button>

<div id="{{ $dialogId }}" popover role="alertdialog" aria-labelledby="{{ $dialogId }}-title"
     class="m-auto h-fit w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 text-left text-gray-900 shadow-xl backdrop:bg-black/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:backdrop:bg-black/60">
    <h2 id="{{ $dialogId }}-title" class="mb-2 text-lg font-semibold">Eliminare il brano?</h2>
    <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
        {{ $song->artist }} - {{ $song->title }} verrà eliminato definitivamente.
    </p>

    <div class="flex justify-end gap-2">
        <button type="button" popovertarget="{{ $dialogId }}" popovertargetaction="hide"
                title="Annulla" aria-label="Annulla"
                class="cursor-pointer rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
            @include('icons.x-mark')
        </button>

        <form method="POST" action="{{ route('songs.destroy', $song) }}">
            @csrf
            @method('DELETE')
            <button type="submit" title="Conferma eliminazione" aria-label="Conferma eliminazione"
                    class="cursor-pointer rounded-md bg-red-700 p-2 text-white hover:bg-red-800">
                @include('icons.trash')
            </button>
        </form>
    </div>
</div>
