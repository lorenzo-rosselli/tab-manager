@extends('layouts.app')

@section('title', 'Brani')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Brani</h1>

        {{-- Ricerca: un form GET mette il testo nell'indirizzo, es. /songs?q=queen.
             Il campo nascosto "sort" conserva l'ordinamento scelto. --}}
        <form method="GET" action="{{ route('songs.index') }}" class="flex items-center gap-1">
            @if ($sortParam !== '')
                <input type="hidden" name="sort" value="{{ $sortParam }}">
            @endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Cerca per artista, titolo, gruppo o strumento"
                   aria-label="Cerca per artista originale, titolo, il mio gruppo o strumento"
                   class="w-128 rounded-md border border-gray-300 bg-white dark:border-gray-600 px-3 py-2 text-sm text-gray-900 scheme-light">
            <button type="submit" title="Cerca" aria-label="Cerca"
                    class="cursor-pointer rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                @include('icons.magnifying-glass')
            </button>
            @if ($search !== '')
                <a href="{{ route('songs.index', array_filter(['sort' => $sortParam])) }}"
                   title="Annulla ricerca" aria-label="Annulla ricerca"
                   class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                    @include('icons.x-mark')
                </a>
            @endif

            {{-- Backup: scarica uno ZIP con tutti i brani in .docx e il database (BackupController). --}}
            <span class="mx-1 h-6 w-px bg-gray-300 dark:bg-gray-600" aria-hidden="true"></span>
            <a href="{{ route('backup') }}" title="Backup: scarica tutti i brani (.docx) e l'archivio in uno ZIP"
               aria-label="Backup di tutti i brani"
               class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                @include('icons.archive-box')
            </a>
        </form>
    </div>

    @if ($songs->isEmpty() && $search !== '')
        <p class="text-gray-500 dark:text-gray-400">Nessun brano trovato per «{{ $search }}».</p>
    @elseif ($songs->isEmpty())
        <p class="text-gray-500 dark:text-gray-400">
            Nessun brano in archivio.
            <a href="{{ route('songs.create') }}" class="text-gray-900 underline dark:text-white">Aggiungi il primo</a>.
        </p>
    @else
        {{-- Ordinamento attivo: riepilogo nell'ordine di priorità e icona per azzerarlo (la ricerca resta). --}}
        @if ($sortParam !== '')
            <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span>Ordinato per:</span>
                @foreach (collect($columns)->whereNotNull('direction')->sortBy('position') as $column)
                    <span class="inline-flex items-center gap-1 rounded-full border border-gray-300 bg-white px-2 py-0.5 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        {{ $column['label'] }}
                        <span class="inline-flex *:size-3.5" aria-hidden="true">
                            @include($column['direction'] === 'asc' ? 'icons.chevron-up' : 'icons.chevron-down')
                        </span>
                        <span class="sr-only">{{ $column['direction'] === 'asc' ? '(crescente)' : '(decrescente)' }}</span>
                    </span>
                @endforeach
                <a href="{{ route('songs.index', array_filter(['q' => $search])) }}"
                   title="Azzera ordinamento" aria-label="Azzera ordinamento"
                   class="rounded-md p-1.5 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                    @include('icons.x-mark')
                </a>
            </div>
        @endif

        <div class="overflow-x-auto rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                    @php($sortedCount = collect($columns)->whereNotNull('direction')->count())
                    <tr>
                        {{-- Intestazioni cliccabili: ogni clic aggiunge la colonna all'ordinamento,
                             poi la inverte, poi la toglie. Il numero indica la priorità. --}}
                        @foreach ($columns as $column)
                            <th class="px-4 py-3 font-medium"
                                @if ($column['direction']) aria-sort="{{ $column['direction'] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                <a href="{{ $column['url'] }}" title="{{ $column['hint'] }}"
                                   class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white {{ $column['direction'] ? 'text-gray-900 dark:text-white' : '' }}">
                                    {{ $column['label'] }}
                                    @if ($column['direction'])
                                        <span class="inline-flex *:size-4">
                                            @include($column['direction'] === 'asc' ? 'icons.chevron-up' : 'icons.chevron-down')
                                        </span>
                                        @if ($sortedCount > 1)
                                            <span class="text-xs">{{ $column['position'] }}</span>
                                        @endif
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 font-medium text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($songs as $song)
                        {{--
                            Riga cliccabile senza JavaScript: il link del titolo ha uno pseudo-elemento
                            (after:absolute after:inset-0) che si allarga su tutta la riga (relative).
                            La cella delle azioni sta sopra (relative z-10), così le icone restano cliccabili.
                        --}}
                        <tr class="relative hover:bg-gray-100 dark:hover:bg-gray-700/60">
                            <td class="px-4 py-3">{{ $song->artist }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('songs.show', $song) }}" class="after:absolute after:inset-0">{{ $song->title }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $song->band ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $song->instrument->label() }}</td>
                            <td class="px-4 py-3">{{ $song->updated_at->format('d/m/Y') }}</td>
                            <td class="relative z-10 px-4 py-3">
                                {{-- Icone: title = tooltip del browser, aria-label = testo per i lettori di schermo --}}
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('songs.edit', $song) }}" title="Modifica" aria-label="Modifica"
                                       class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                        @include('icons.pencil')
                                    </a>
                                    <a href="{{ route('songs.download', $song) }}" title="Scarica .docx" aria-label="Scarica .docx"
                                       class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                        @include('icons.download')
                                    </a>
                                    @include('songs._delete')
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
