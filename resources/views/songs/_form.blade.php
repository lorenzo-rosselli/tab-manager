{{--
    Campi comuni ai form "Nuovo brano" e "Modifica brano".
    Riceve: $song (brano da modificare, oppure un Song vuoto) e $submitLabel (testo del pulsante).
    old('campo', valore) mostra prima ciò che l'utente aveva scritto (se la validazione è fallita),
    altrimenti il valore salvato nel database.
--}}
<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="title" class="mb-1 block text-sm font-medium">Titolo</label>
            <input type="text" id="title" name="title" value="{{ old('title', $song->title) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2">
            @error('title') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="artist" class="mb-1 block text-sm font-medium">Artista</label>
            <input type="text" id="artist" name="artist" value="{{ old('artist', $song->artist) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2">
            @error('artist') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="instrument" class="mb-1 block text-sm font-medium">Strumento</label>
            <select id="instrument" name="instrument" class="w-full rounded-md border border-stone-300 px-3 py-2">
                @foreach (\App\Enums\Instrument::cases() as $instrument)
                    <option value="{{ $instrument->value }}"
                        @selected(old('instrument', $song->instrument?->value) === $instrument->value)>
                        {{ $instrument->label() }}
                    </option>
                @endforeach
            </select>
            @error('instrument') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tuning" class="mb-1 block text-sm font-medium">Accordatura</label>
            <input type="text" id="tuning" name="tuning" list="tunings"
                   value="{{ old('tuning', $song->tuning) }}" placeholder="E A D G"
                   class="w-full rounded-md border border-stone-300 px-3 py-2">
            {{-- Suggerimenti mostrati dal browser mentre si scrive (HTML puro, nessun JavaScript) --}}
            <datalist id="tunings">
                <option value="E A D G">Basso 4 corde</option>
                <option value="B E A D G">Basso 5 corde</option>
                <option value="E A D G B E">Chitarra 6 corde</option>
            </datalist>
            @error('tuning') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="tab" class="mb-1 block text-sm font-medium">Tabulatura</label>
        <textarea id="tab" name="tab" rows="20" wrap="off" spellcheck="false"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 font-mono text-sm">{{ old('tab', $song->tab) }}</textarea>
        <p class="mt-1 text-sm text-stone-500">
            Nel file Word una riga sta in circa 80 caratteri: le righe più lunghe andranno a capo.
        </p>
        @error('tab') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="notes" class="mb-1 block text-sm font-medium">Note <span class="text-stone-500">(facoltative)</span></label>
        <textarea id="notes" name="notes" rows="3"
                  class="w-full rounded-md border border-stone-300 px-3 py-2">{{ old('notes', $song->notes) }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <button type="submit" title="{{ $submitLabel }}" aria-label="{{ $submitLabel }}"
                class="cursor-pointer rounded-md bg-stone-900 p-2 text-white hover:bg-stone-700">
            @include('icons.save')
        </button>
        <a href="{{ url()->previous() }}" title="Annulla" aria-label="Annulla"
           class="rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
            @include('icons.x-mark')
        </a>
    </div>
</div>
