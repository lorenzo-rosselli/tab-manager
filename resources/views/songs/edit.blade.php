@extends('layouts.app')

@section('title', 'Modifica: '.$song->title)

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Modifica brano</h1>

    <form method="POST" action="{{ route('songs.update', $song) }}">
        @csrf
        @method('PUT')
        @include('songs._form', ['submitLabel' => 'Salva modifiche'])
    </form>
@endsection
