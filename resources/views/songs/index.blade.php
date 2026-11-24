@extends('layouts.app')

@section('title', 'Brani')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">Brani</h1>

        {{-- Ricerca: un form GET mette il testo nell'indirizzo, es. /songs?q=queen --}}
        <form method="GET" action="{{ route('songs.index') }}" class="flex items-center gap-1">
            <input type="search" name="q" value="{{ $search }}" placeholder="Cerca titolo o artista"
                   aria-label="Cerca titolo o artista"
                   class="w-64 rounded-md border border-stone-300 px-3 py-2 text-sm">
            <button type="submit" title="Cerca" aria-label="Cerca"
                    class="cursor-pointer rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
                @include('icons.magnifying-glass')
            </button>
            @if ($search !== '')
                <a href="{{ route('songs.index') }}" title="Annulla ricerca" aria-label="Annulla ricerca"
                   class="rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
                    @include('icons.x-mark')
                </a>
            @endif
        </form>
    </div>

    @if ($songs->isEmpty() && $search !== '')
        <p class="text-stone-600">Nessun brano trovato per «{{ $search }}».</p>
    @elseif ($songs->isEmpty())
        <p class="text-stone-600">
            Nessun brano in archivio.
            <a href="{{ route('songs.create') }}" class="underline">Aggiungi il primo</a>.
        </p>
    @else
        <div class="overflow-x-auto rounded-md border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-stone-100 text-stone-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Titolo</th>
                        <th class="px-4 py-3 font-medium">Artista</th>
                        <th class="px-4 py-3 font-medium">Strumento</th>
                        <th class="px-4 py-3 font-medium">Data</th>
                        <th class="px-4 py-3 font-medium text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200">
                    @foreach ($songs as $song)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $song->title }}</td>
                            <td class="px-4 py-3">{{ $song->artist }}</td>
                            <td class="px-4 py-3">{{ $song->instrument->label() }}</td>
                            <td class="px-4 py-3">{{ $song->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                {{-- Icone: title = tooltip del browser, aria-label = testo per i lettori di schermo --}}
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('songs.show', $song) }}" title="Vedi" aria-label="Vedi"
                                       class="rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
                                        @include('icons.eye')
                                    </a>
                                    <a href="{{ route('songs.edit', $song) }}" title="Modifica" aria-label="Modifica"
                                       class="rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
                                        @include('icons.pencil')
                                    </a>
                                    <a href="{{ route('songs.download', $song) }}" title="Scarica .docx" aria-label="Scarica .docx"
                                       class="rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
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
