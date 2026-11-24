@extends('layouts.app')

@section('title', $song->title)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $song->title }}</h1>
            <p class="text-lg italic text-stone-600">{{ $song->artist }}</p>
            <p class="mt-2 text-sm text-stone-600">
                Strumento: {{ $song->instrument->label() }} – Accordatura: {{ $song->tuning }}
            </p>
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ route('songs.download', $song) }}" title="Scarica .docx" aria-label="Scarica .docx"
               class="rounded-md bg-stone-900 p-2 text-white hover:bg-stone-700">
                @include('icons.download')
            </a>
            <a href="{{ route('songs.edit', $song) }}" title="Modifica" aria-label="Modifica"
               class="rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
                @include('icons.pencil')
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
    </div>

    @if ($song->notes)
        <p class="mb-6 whitespace-pre-line rounded-md border border-stone-200 bg-white px-4 py-3 text-sm">{{ $song->notes }}</p>
    @endif

    {{-- <pre> mantiene spazi e a capo; font-mono allinea le colonne della tabulatura --}}
    <pre class="overflow-x-auto rounded-md border border-stone-200 bg-white p-4 font-mono text-sm leading-tight">{{ $song->tab }}</pre>

    <p class="mt-6">
        <a href="{{ route('songs.index') }}" title="Torna all'elenco" aria-label="Torna all'elenco"
           class="inline-block rounded-md p-2 text-stone-600 hover:bg-stone-100 hover:text-stone-900">
            @include('icons.arrow-left')
        </a>
    </p>
@endsection
