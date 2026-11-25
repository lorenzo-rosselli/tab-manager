@extends('layouts.app')

@section('title', 'Nuovo brano')

@section('content')
    <form method="POST" action="{{ route('songs.store') }}">
        @csrf
        @include('songs._form-header', ['title' => 'Nuovo brano', 'submitLabel' => 'Salva brano'])
        @include('songs._form', ['song' => new \App\Models\Song])
    </form>
@endsection
