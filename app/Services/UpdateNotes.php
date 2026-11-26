<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Note dell'ultimo aggiornamento automatico, da mostrare una sola volta.
 *
 * Dopo un aggiornamento riuscito, windows\update.ps1 scrive in storage/app/private/update-notes.json
 * il tag della Release di GitHub (es. "v1.1.0") e le sue note (il testo della sezione
 * "Release notes", in Markdown). Alla prima pagina aperta il layout legge il file con pull(),
 * che lo cancella subito: la finestra "Tab-Manager aggiornato..." compare una volta sola.
 * La cartella storage resta al suo posto durante gli aggiornamenti, quindi il file non si perde.
 */
class UpdateNotes
{
    public function path(): string
    {
        return storage_path('app/private/update-notes.json');
    }

    /**
     * Legge e cancella le note. Null se non c'è stato un aggiornamento (o il file non è leggibile).
     *
     * @return array{version: string, notes: string}|null  notes è HTML, già sicuro da stampare
     */
    public function pull(): ?array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        @unlink($path);

        if (! is_array($data) || ! is_string($data['version'] ?? null) || trim($data['version']) === '') {
            return null;
        }

        $notes = trim((string) ($data['notes'] ?? ''));

        return [
            // "v1.1.0" → "1.1.0"
            'version' => ltrim(trim($data['version']), 'vV'),
            // Markdown → HTML. L'HTML scritto a mano nelle note viene tolto e i link pericolosi
            // (javascript:, data:...) non vengono creati: il risultato si può stampare con {!! !!}.
            'notes' => $notes === '' ? '' : Str::markdown($notes, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ];
    }
}
