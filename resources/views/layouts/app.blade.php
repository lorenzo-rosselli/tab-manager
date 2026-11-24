<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Brani') – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="{{ route('songs.index') }}" class="text-xl font-semibold">{{ config('app.name') }}</a>
            <a href="{{ route('songs.create') }}" title="Nuovo brano" aria-label="Nuovo brano"
               class="rounded-md bg-stone-900 p-2 text-white hover:bg-stone-700">
                @include('icons.plus')
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{-- Messaggio flash: impostato dal controller con ->with('status', '...') --}}
        @if (session('status'))
            <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
