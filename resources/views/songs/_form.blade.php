{{--
    Campi comuni ai form "Nuovo brano" e "Modifica brano".
    Riceve: $song (brano da modificare, oppure un Song vuoto).
    I pulsanti Salva e Annulla sono in alto, accanto al titolo della pagina (songs/_form-header).
    old('campo', valore) mostra prima ciò che l'utente aveva scritto (se la validazione è fallita),
    altrimenti il valore salvato nel database.
--}}
@use('App\Enums\Instrument')
@use('App\Models\Song')
@use('App\Services\TabDocument')

@php
    $field = 'w-full rounded-md border border-gray-300 bg-white dark:border-gray-600 px-3 py-2 text-gray-900 scheme-light';
    $check = 'size-4 rounded border-gray-400 accent-gray-700 dark:accent-gray-200';
    $button = 'flex size-8 cursor-pointer items-center justify-center rounded text-gray-700 hover:bg-gray-200 '
        .'aria-pressed:bg-gray-300 aria-pressed:text-gray-900 aria-pressed:ring-1 aria-pressed:ring-gray-400 '
        .'dark:text-gray-200 dark:hover:bg-gray-700 dark:aria-pressed:bg-gray-600 dark:aria-pressed:text-white';

    // Il foglio viaggia come testo JSON in un campo nascosto: quello appena inviato (se la validazione
    // è fallita), altrimenti quello salvato, altrimenti un documento vuoto.
    $tabJson = old('tab', json_encode($song->tab ?? TabDocument::empty()));

    // Strumento e numero di corde scelti. Per un brano nuovo: il primo strumento (Basso) con il suo
    // numero di corde proposto (4); se il brano non ha il numero di corde, quello proposto per il suo strumento.
    $currentInstrument = old('instrument', $song->instrument?->value ?? Instrument::cases()[0]->value);
    $currentStrings = (string) old('strings', $song->strings ?? Instrument::tryFrom($currentInstrument)?->defaultStrings());
@endphp

<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="artist" class="mb-1 block text-sm font-medium">Artista</label>
            <input type="text" id="artist" name="artist" value="{{ old('artist', $song->artist) }}" class="{{ $field }}">
            @error('artist') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="title" class="mb-1 block text-sm font-medium">Titolo</label>
            <input type="text" id="title" name="title" value="{{ old('title', $song->title) }}" class="{{ $field }}">
            @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Ordine dei campi: Artista, Titolo / Strumento, Corde / Gruppo.
             Accanto a Strumento e Gruppo: spunta per scriverli sul foglio, sotto "Artista - Titolo".
             Il campo nascosto "0" viene inviato se la casella è vuota. --}}
        <div>
            <div class="mb-1 flex items-center justify-between gap-4">
                <label for="instrument" class="text-sm font-medium">Strumento</label>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="hidden" name="show_instrument" value="0">
                    <input type="checkbox" id="show_instrument" name="show_instrument" value="1" class="{{ $check }}"
                           @checked(old('show_instrument', $song->show_instrument))>
                    Mostra nel foglio
                </label>
            </div>
            <select id="instrument" name="instrument" class="{{ $field }}">
                @foreach (Instrument::cases() as $instrument)
                    <option value="{{ $instrument->value }}"
                        @selected($currentInstrument === $instrument->value)>
                        {{ $instrument->label() }}
                    </option>
                @endforeach
            </select>
            @error('instrument') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Numero di corde, accanto allo strumento. Ci sono le scelte di tutti gli strumenti
             (Instrument::tunings()), ognuna con data-instrument; lo script dell'editor mostra solo quelle
             dello strumento scelto e propone la prima (basso 4, chitarra 6). Con la voce resta solo "—"
             e il menu è disattivato. data-tuning: le corde, per il pulsante "Inserisci tabulatura vuota". --}}
        <div>
            <div class="mb-1 flex items-center">
                <label for="strings" class="text-sm font-medium">Corde</label>
            </div>
            <select id="strings" name="strings" class="{{ $field }} disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                <option value="">—</option>
                @foreach (Instrument::cases() as $instrument)
                    @foreach ($instrument->tunings() as $count => $tuning)
                        <option value="{{ $count }}" data-instrument="{{ $instrument->value }}" data-tuning="{{ implode(' ', $tuning) }}"
                            @selected($currentInstrument === $instrument->value && $currentStrings === (string) $count)>
                            {{ $count }} corde
                        </option>
                    @endforeach
                @endforeach
            </select>
            @error('strings') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between gap-4">
                <label for="band" class="text-sm font-medium">Gruppo <span class="text-gray-500 dark:text-gray-400">(facoltativo)</span></label>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="hidden" name="show_band" value="0">
                    <input type="checkbox" id="show_band" name="show_band" value="1" class="{{ $check }}"
                           @checked(old('show_band', $song->show_band))>
                    Mostra nel foglio
                </label>
            </div>
            <input type="text" id="band" name="band" value="{{ old('band', $song->band) }}"
                   placeholder="La band con cui suoni il brano" class="{{ $field }}">
            @error('band') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    {{--
        Il foglio: è esattamente il documento Word che verrà scaricato.
        - In alto la barra di formattazione (come in Word): agisce sul testo selezionato
          (font, dimensione, G/C/S) o sui paragrafi selezionati (allineamento).
        - Sotto, le pagine A4 (21 × 29,7 cm) con margini di 2 cm, disegnate da resources/js/tab-pages.js;
          la zona scrivibile (17 × 25,7 cm, come nel .docx) è segnata da un bordo tratteggiato,
          il testo va a capo al margine e passa alla pagina successiva quando la pagina è piena.
        - Gli ultimi pulsanti della barra inseriscono un'interruzione di pagina (anche con Ctrl+Invio)
          e, se è scelto il numero di corde, una tabulatura vuota in fondo al foglio.
        L'editor (Tiptap) è in resources/js/tab-editor.js e scrive il documento nel campo nascosto "tab".
        data-home: l'indirizzo dell'elenco, dove porta il tasto Esc.
    --}}
    <div>
        <span class="mb-1 block text-sm font-medium" id="tab-label">Tabulatura</span>

        <div class="rounded-md border border-gray-300 dark:border-gray-700">
            <div id="tab-toolbar" role="toolbar" aria-labelledby="tab-label"
                 class="flex flex-wrap items-center gap-1 rounded-t-md border-b border-gray-300 bg-gray-100 dark:border-gray-700 dark:bg-gray-950 px-3 py-2">
                <select id="tab-font" title="Font" aria-label="Font"
                        class="w-44 rounded border border-gray-300 bg-white dark:border-gray-600 px-2 py-1 text-sm text-gray-900 scheme-light">
                    @foreach (Song::FONTS as $option)
                        <option value="{{ $option }}" style="font-family: '{{ $option }}', monospace">{{ $option }}</option>
                    @endforeach
                </select>

                <select id="tab-size" title="Dimensione carattere" aria-label="Dimensione carattere"
                        class="w-20 rounded border border-gray-300 bg-white dark:border-gray-600 px-2 py-1 text-sm text-gray-900 scheme-light">
                    @foreach (Song::FONT_SIZES as $option)
                        <option value="{{ $option }}">{{ $option }} pt</option>
                    @endforeach
                </select>

                <span class="mx-2 h-6 w-px bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>

                {{-- G / C / S come nel Word italiano --}}
                @foreach (['bold' => ['G', 'Grassetto', 'font-bold', 'toggleBold'], 'italic' => ['C', 'Corsivo', 'italic font-serif', 'toggleItalic'], 'underline' => ['S', 'Sottolineato', 'underline', 'toggleUnderline']] as $mark => [$letter, $label, $style, $command])
                    <button type="button" title="{{ $label }}" aria-label="{{ $label }}" aria-pressed="false"
                            data-command="{{ $command }}" data-mark="{{ $mark }}" class="{{ $button }}">
                        <span class="text-lg leading-none {{ $style }}" aria-hidden="true">{{ $letter }}</span>
                    </button>
                @endforeach

                <span class="mx-2 h-6 w-px bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>

                {{-- Colore del testo ed evidenziatore: menu a tendina (<details>) con la tavolozza.
                     La barretta colorata sotto l'icona mostra il colore attuale. --}}
                @foreach ([
                    'color' => ['Colore carattere', 'Automatico', Song::TEXT_COLORS, 'tab-color-bar'],
                    'highlight' => ['Colore evidenziazione testo', 'Nessuno', array_map(fn ($h) => $h[0], Song::HIGHLIGHTS), 'tab-highlight-bar'],
                ] as $palette => [$label, $none, $colors, $barId])
                    <details class="relative">
                        <summary title="{{ $label }}" aria-label="{{ $label }}"
                                 class="flex h-8 w-10 cursor-pointer list-none flex-col items-center justify-center gap-0.5 rounded text-gray-700 hover:bg-gray-200 dark:text-gray-200 dark:hover:bg-gray-700 [&::-webkit-details-marker]:hidden">
                            @if ($palette === 'color')
                                <span class="text-base font-bold leading-none" aria-hidden="true">A</span>
                            @else
                                <span class="*:size-4" aria-hidden="true">@include('icons.highlighter')</span>
                            @endif
                            <span id="{{ $barId }}" class="h-1 w-5 rounded-sm border border-gray-600" aria-hidden="true"></span>
                        </summary>
                        <div class="absolute left-0 top-full z-20 mt-1 w-44 rounded-md border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                            <button type="button" data-palette="{{ $palette }}" data-value=""
                                    class="mb-2 flex w-full cursor-pointer items-center gap-2 rounded px-2 py-1 text-left text-sm text-gray-900 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-700">
                                <span class="size-4 rounded-sm border border-gray-500 {{ $palette === 'color' ? 'bg-black' : 'bg-white' }}"></span>
                                {{ $none }}
                            </button>
                            <div class="grid grid-cols-5 gap-1">
                                @foreach ($colors as $hex => $name)
                                    <button type="button" data-palette="{{ $palette }}" data-value="{{ $hex }}"
                                            title="{{ $name }}" aria-label="{{ $name }}"
                                            class="size-7 cursor-pointer rounded-sm border border-gray-300 hover:ring-2 hover:ring-gray-900 dark:border-gray-600 dark:hover:ring-white"
                                            style="background-color: {{ $hex }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endforeach

                <span class="mx-2 h-6 w-px bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>

                @foreach (Song::ALIGNMENTS as $value => $label)
                    <button type="button" title="{{ $label }}" aria-label="{{ $label }}" aria-pressed="false"
                            data-command="align" data-value="{{ $value }}" class="{{ $button }}">
                        @include('icons.align-'.$value)
                    </button>
                @endforeach

                <span class="mx-2 h-6 w-px bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>

                <button type="button" title="Interruzione di pagina (Ctrl+Invio)" aria-label="Interruzione di pagina"
                        data-command="setPageBreak" class="{{ $button }}">
                    @include('icons.page-break')
                </button>

                {{-- Attivo solo con il numero di corde scelto: lo script aggiorna stato e tooltip. --}}
                <button type="button" id="tab-staff" title="Inserisci tabulatura vuota (scegli prima il numero di corde)"
                        aria-label="Inserisci tabulatura vuota" disabled
                        class="{{ $button }} disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent">
                    @include('icons.tab-staff')
                </button>
            </div>

            <div class="overflow-x-auto rounded-b-md bg-gray-200 p-6 dark:bg-gray-800 sm:p-10">
                <div class="tab-paper">
                    <div class="tab-paper-pages" aria-hidden="true"></div>
                    <div id="tab-editor" data-home="{{ route('songs.index') }}" class="tab-sheet"></div>
                </div>
            </div>
        </div>

        <input type="hidden" id="tab" name="tab" value="{{ $tabJson }}">

        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Il foglio è il documento Word: stessa pagina A4, stessi margini, stessa formattazione.
            La formattazione si applica al testo selezionato.
        </p>
        @error('tab') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        @foreach (['show_band', 'show_instrument'] as $name)
            @error($name) <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        @endforeach
    </div>

    @vite('resources/js/tab-editor.js')
</div>
