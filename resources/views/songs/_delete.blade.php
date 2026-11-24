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
        class="cursor-pointer rounded-md p-2 text-red-700 hover:bg-red-50 hover:text-red-800">
    @include('icons.trash')
</button>

<div id="{{ $dialogId }}" popover role="alertdialog" aria-labelledby="{{ $dialogId }}-title"
     class="m-auto h-fit w-full max-w-md rounded-lg border border-stone-200 bg-white p-6 text-left text-stone-900 shadow-xl backdrop:bg-stone-900/40">
    <h2 id="{{ $dialogId }}-title" class="mb-2 text-lg font-semibold">Eliminare il brano?</h2>
    <p class="mb-6 text-sm text-stone-600">
        «{{ $song->title }}» di {{ $song->artist }} verrà eliminato definitivamente.
    </p>

    <div class="flex justify-end gap-2">
        <button type="button" popovertarget="{{ $dialogId }}" popovertargetaction="hide"
                title="Annulla" aria-label="Annulla"
                class="cursor-pointer rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
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
