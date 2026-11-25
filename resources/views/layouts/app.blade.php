@php
    // Tema scelto con l'interruttore (cookie "theme"); senza scelta: scuro.
    $dark = request()->cookie('theme') !== 'light';
@endphp
<!DOCTYPE html>
{{-- "dark" attiva le classi dark:...; scheme-dark/scheme-light dicono al browser di che colore fare
     barre di scorrimento e controlli. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $dark ? 'dark scheme-dark' : 'scheme-light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Brani') – {{ config('app.name') }}</title>
    {{-- Icona dell'app (scheda del browser, finestra dedicata e barra delle applicazioni) --}}
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 24x24 32x32 48x48 64x64 128x128 256x256">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-950">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="{{ route('songs.index') }}" class="flex items-center gap-3 text-xl font-semibold text-gray-900 dark:text-white">
                @include('icons.logo')
                {{ config('app.name') }}
            </a>

            <div class="flex items-center gap-3">
                {{--
                    Interruttore del tema: in chiaro il sole a sinistra e il pomello a destra,
                    in scuro il pomello a sinistra e la luna a destra. L'aspetto dipende solo dalla
                    classe "dark" su <html>; il clic lo gestisce resources/js/app.js.
                --}}
                <button type="button" id="theme-toggle" role="switch" aria-checked="{{ $dark ? 'true' : 'false' }}"
                        title="Tema scuro" aria-label="Tema scuro"
                        class="relative h-8 w-16 cursor-pointer rounded-full border border-gray-300 bg-gray-200 shadow-inner transition-colors dark:border-gray-600 dark:bg-gray-700">
                    <span class="absolute top-1/2 left-2 -translate-y-1/2 text-amber-500 *:size-4 dark:opacity-0">@include('icons.sun')</span>
                    <span class="absolute top-1/2 right-2 -translate-y-1/2 text-amber-400 opacity-0 *:size-4 *:fill-current dark:opacity-100">@include('icons.moon')</span>
                    <span class="absolute top-0.5 left-0.5 size-6 translate-x-8 rounded-full bg-white shadow-md transition-transform dark:translate-x-0 dark:bg-gray-200"></span>
                </button>

                <a href="{{ route('songs.create') }}" title="Nuovo brano" aria-label="Nuovo brano"
                   class="rounded-md bg-gray-900 p-2 text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                    @include('icons.plus')
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{-- Messaggio flash: impostato dal controller con ->with('status', '...') --}}
        @if (session('status'))
            <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
