@extends('layouts.app')

@section('title', 'Nuovo brano')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Nuovo brano</h1>

    <form method="POST" action="{{ route('songs.store') }}">
        @csrf
        @include('songs._form', ['song' => new \App\Models\Song, 'submitLabel' => 'Salva brano'])
    </form>
@endsection
