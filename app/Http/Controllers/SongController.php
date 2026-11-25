<?php

namespace App\Http\Controllers;

use App\Enums\Instrument;
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
     * Colonne ordinabili dell'elenco: chiave usata nell'indirizzo => [colonna del database, etichetta].
     */
    private const SORTABLE = [
        'artist' => ['artist', 'Artista'],
        'title' => ['title', 'Titolo'],
        'band' => ['band', 'Gruppo'],
        'instrument' => ['instrument', 'Strumento'],
        'date' => ['created_at', 'Data'],
    ];

    /**
     * Elenco dei brani.
     * - ?q=testo     mostra solo i brani con il testo nel titolo, nell'artista, nel gruppo
     *                o nel nome dello strumento (es. "basso");
     * - ?sort=a,-b   ordina per a (crescente) e poi per b (decrescente, segno "-"),
     *                nell'ordine in cui le colonne sono state scelte. Senza sort: dal più recente.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $sorts = $this->parseSorts((string) $request->query('sort', ''));

        // Nel database lo strumento è salvato come "bass"/"guitar"/"voice": si cerca il testo nei nomi italiani
        // ("Basso", "Chitarra", "Voce") e si filtra sui valori corrispondenti.
        $instruments = collect(Instrument::cases())
            ->filter(fn (Instrument $instrument) => str_contains(mb_strtolower($instrument->label()), mb_strtolower($search)))
            ->map(fn (Instrument $instrument) => $instrument->value)
            ->values()
            ->all();

        $query = Song::query()
            ->when($search !== '', function ($query) use ($search, $instruments) {
                $query->where(function ($query) use ($search, $instruments) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('artist', 'like', "%{$search}%")
                        ->orWhere('band', 'like', "%{$search}%")
                        ->when($instruments !== [], fn ($query) => $query->orWhereIn('instrument', $instruments));
                });
            });

        foreach ($sorts as $key => $direction) {
            $query->orderBy(self::SORTABLE[$key][0], $direction);
        }

        if ($sorts === []) {
            $query->latest();
        }

        return view('songs.index', [
            'songs' => $query->get(),
            'search' => $search,
            'sortParam' => $this->sortParam($sorts),
            'columns' => $this->sortColumns($sorts, $search),
        ]);
    }

    /**
     * Legge il parametro sort: "artist,-title" diventa ['artist' => 'asc', 'title' => 'desc'].
     * Le chiavi sconosciute o ripetute vengono ignorate.
     *
     * @return array<string, 'asc'|'desc'>
     */
    private function parseSorts(string $param): array
    {
        $sorts = [];

        foreach (explode(',', $param) as $item) {
            $item = trim($item);
            $key = ltrim($item, '-');

            if (isset(self::SORTABLE[$key]) && ! isset($sorts[$key])) {
                $sorts[$key] = str_starts_with($item, '-') ? 'desc' : 'asc';
            }
        }

        return $sorts;
    }

    /**
     * Operazione inversa: ['artist' => 'asc', 'title' => 'desc'] diventa "artist,-title".
     *
     * @param  array<string, 'asc'|'desc'>  $sorts
     */
    private function sortParam(array $sorts): string
    {
        return collect($sorts)
            ->map(fn (string $direction, string $key) => ($direction === 'desc' ? '-' : '').$key)
            ->implode(',');
    }

    /**
     * Dati per le intestazioni della tabella: per ogni colonna l'etichetta, lo stato attuale
     * (direzione e posizione) e il link del clic successivo: crescente → decrescente → tolta.
     *
     * @param  array<string, 'asc'|'desc'>  $sorts
     * @return list<array{label: string, url: string, hint: string, direction: ?string, position: ?int}>
     */
    private function sortColumns(array $sorts, string $search): array
    {
        $positions = array_flip(array_keys($sorts));
        $columns = [];

        foreach (self::SORTABLE as $key => [, $label]) {
            $next = $sorts;
            $current = $sorts[$key] ?? null;

            if ($current === null) {
                $next[$key] = 'asc';
                $hint = "Ordina per {$label} (crescente)";
            } elseif ($current === 'asc') {
                $next[$key] = 'desc';
                $hint = "Ordina per {$label} (decrescente)";
            } else {
                unset($next[$key]);
                $hint = "Togli l'ordinamento per {$label}";
            }

            $columns[] = [
                'label' => $label,
                'url' => route('songs.index', array_filter(['q' => $search, 'sort' => $this->sortParam($next)])),
                'hint' => $hint,
                'direction' => $current,
                'position' => isset($positions[$key]) ? $positions[$key] + 1 : null,
            ];
        }

        return $columns;
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
        $song = Song::create($request->songData());

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
        $song->update($request->songData());

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
