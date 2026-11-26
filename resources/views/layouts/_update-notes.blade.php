{{--
    Finestra "Tab-Manager aggiornato alla versione ...!" (modale con l'API Popover, come quella
    dell'eliminazione). C'è solo nella prima pagina aperta dopo un aggiornamento automatico
    ($updateNotes, da App\Services\UpdateNotes) e si apre da sola (resources/js/app.js).
    Si chiude con l'icona, con Esc o cliccando fuori.
    Le note sono quelle della Release di GitHub, già convertite da Markdown in HTML sicuro.
--}}
@if ($updateNotes)
    <div id="update-notes" popover role="dialog" aria-labelledby="update-notes-title"
         class="m-auto h-fit max-h-[80vh] w-full max-w-lg overflow-y-auto rounded-lg border border-gray-200 bg-white p-6 text-left text-gray-900 shadow-xl backdrop:bg-black/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:backdrop:bg-black/60">
        <h2 id="update-notes-title" class="mb-4 text-lg font-semibold">
            Tab-Manager aggiornato alla versione {{ $updateNotes['version'] }}!
        </h2>

        @if ($updateNotes['notes'] !== '')
            <div class="release-notes text-sm text-gray-700 dark:text-gray-300">
                {!! $updateNotes['notes'] !!}
            </div>
        @endif

        <div class="mt-6 flex justify-end">
            <button type="button" popovertarget="update-notes" popovertargetaction="hide"
                    title="Chiudi" aria-label="Chiudi"
                    class="cursor-pointer rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                @include('icons.x-mark')
            </button>
        </div>
    </div>
@endif
