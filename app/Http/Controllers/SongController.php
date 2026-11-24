<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongRequest;
use App\Models\Song;
use App\Services\TabDocxGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SongController extends Controller
{
    /**
     * Elenco dei brani, dal più recente.
     * (La ricerca per titolo/artista arriva nella tappa 6.)
     */
    public function index(): View
    {
        $songs = Song::latest()->get();

        return view('songs.index', ['songs' => $songs]);
    }

    /**
     * Form per un nuovo brano.
     */
    public function create(): View
    {
        return view('songs.create');
    }

    /**
     * Salva un nuovo brano.
     * Arriva qui solo se SongRequest ha già validato i dati.
     */
    public function store(SongRequest $request): RedirectResponse
    {
        $song = Song::create($request->validated());

        return redirect()->route('songs.show', $song);
    }

    /**
     * Anteprima di un brano con la sua tabulatura.
     */
    public function show(Song $song): View
    {
        return view('songs.show', ['song' => $song]);
    }

    /**
     * Form di modifica di un brano esistente.
     */
    public function edit(Song $song): View
    {
        return view('songs.edit', ['song' => $song]);
    }

    /**
     * Aggiorna un brano esistente.
     */
    public function update(SongRequest $request, Song $song): RedirectResponse
    {
        $song->update($request->validated());

        return redirect()->route('songs.show', $song);
    }

    /**
     * Elimina un brano e torna all'elenco.
     */
    public function destroy(Song $song): RedirectResponse
    {
        $song->delete();

        return redirect()->route('songs.index');
    }

    /**
     * Genera e scarica il file .docx del brano.
     * Laravel crea da solo il TabDocxGenerator e lo passa al metodo (dependency injection).
     */
    public function download(Song $song, TabDocxGenerator $generator): BinaryFileResponse
    {
        $path = $generator->generate($song);

        return response()
            ->download($path, $generator->filename($song), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend();
    }
}
