<?php

use App\Http\Controllers\SongController;
use Illuminate\Support\Facades\Route;

// La home porta direttamente all'elenco dei brani.
Route::redirect('/', '/songs');

// Le 7 rotte standard: index, create, store, show, edit, update, destroy.
Route::resource('songs', SongController::class);

// Download della tabulatura in formato Word.
Route::get('songs/{song}/docx', [SongController::class, 'download'])->name('songs.download');
