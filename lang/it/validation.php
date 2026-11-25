<?php

/*
|--------------------------------------------------------------------------
| Messaggi di validazione in italiano
|--------------------------------------------------------------------------
| Laravel li usa perché in .env c'è APP_LOCALE=it.
| Ci sono solo le regole usate dall'app (vedi SongRequest): per tutte le altre
| Laravel ripiega sui messaggi inglesi (APP_FALLBACK_LOCALE=en).
| :attribute viene sostituito con il nome del campo (vedi 'attributes' in fondo).
*/

return [

    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => 'Il campo :attribute deve essere un testo.',
    'in' => 'Il valore scelto per :attribute non è valido.',
    'boolean' => 'Il campo :attribute deve essere sì o no.',
    'json' => 'Il contenuto di :attribute non è valido.',

    'max' => [
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],

    // Nomi dei campi come li vede l'utente.
    'attributes' => [
        'title' => 'titolo',
        'artist' => 'artista',
        'band' => 'gruppo',
        'instrument' => 'strumento',
        'strings' => 'numero di corde',
        'show_instrument' => 'mostra strumento',
        'show_band' => 'mostra gruppo',
        'tab' => 'tabulatura',
    ],

];
