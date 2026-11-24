<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongRequest;
use App\Models\Song;
use App\Services\TabDocxGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SongController extends Controller
{
    /**
     * Elenco dei brani, dal più recente.
     * Con ?q=... nell'indirizzo mostra solo i brani il cui titolo o artista contiene il testo cercato.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $songs = Song::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('artist', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('songs.index', ['songs' => $songs, 'search' => $search]);
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

        return redirect()->route('songs.show', $song)->with('status', 'Brano salvato.');
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

        return redirect()->route('songs.show', $song)->with('status', 'Modifiche salvate.');
    }

    /**
     * Elimina un brano e torna all'elenco.
     */
    public function destroy(Song $song): RedirectResponse
    {
        $song->delete();

        return redirect()->route('songs.index')->with('status', 'Brano eliminato.');
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
