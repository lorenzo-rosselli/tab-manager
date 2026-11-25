@extends('layouts.app')

@section('title', $song->title)

@section('content')
    {{-- data-escape-to: il tasto Esc porta all'elenco (vedi resources/js/app.js) --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4" data-escape-to="{{ route('songs.index') }}">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $song->title }}</h1>
            <p class="text-lg italic text-gray-600 dark:text-gray-300">{{ $song->artist }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Strumento: {{ $song->instrument->label() }}
                @if ($song->band)
                    – Gruppo: {{ $song->band }}
                @endif
            </p>
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ route('songs.download', $song) }}" title="Scarica .docx" aria-label="Scarica .docx"
               class="rounded-md bg-gray-900 p-2 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                @include('icons.download')
            </a>
            <a href="{{ route('songs.edit', $song) }}" title="Modifica" aria-label="Modifica"
               class="rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                @include('icons.pencil')
            </a>
            @include('songs._delete')
        </div>
    </div>

    {{-- Il foglio, come nel Word: A4 (21 cm) con margini di 2 cm.
         TabDocument::toHtml() fa l'escaping di tutto il testo, quindi si può stampare con {!! !!}. --}}
    <div class="overflow-x-auto rounded-md bg-gray-200 p-6 dark:bg-gray-800 sm:p-10">
        <div class="mx-auto w-[21cm] bg-white p-[2cm] shadow-lg shadow-black/20 dark:shadow-black/50">
            <div class="tab-sheet">{!! \App\Services\TabDocument::toHtml($song->tab) !!}</div>
        </div>
    </div>

    <p class="mt-6">
        <a href="{{ route('songs.index') }}" title="Torna all'elenco" aria-label="Torna all'elenco"
           class="inline-block rounded-md p-2 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
            @include('icons.arrow-left')
        </a>
    </p>
@endsection
