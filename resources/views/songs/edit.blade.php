@extends('layouts.app')

@section('title', 'Modifica: '.$song->title)

@section('content')
    <form method="POST" action="{{ route('songs.update', $song) }}">
        @csrf
        @method('PUT')
        @include('songs._form-header', ['title' => 'Modifica brano', 'submitLabel' => 'Salva modifiche', 'cancelUrl' => route('songs.show', $song)])
        @include('songs._form')
    </form>
@endsection
