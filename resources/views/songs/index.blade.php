@extends('layouts.app')

@section('title', 'Brani')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Brani</h1>

    @if ($songs->isEmpty())
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
                                    <form method="POST" action="{{ route('songs.destroy', $song) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Elimina" aria-label="Elimina"
                                                class="cursor-pointer rounded-md p-2 text-red-700 hover:bg-red-50 hover:text-red-800">
                                            @include('icons.trash')
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
