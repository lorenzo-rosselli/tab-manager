<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Copie di sicurezza dell'archivio dei brani.
 *
 * - Copie automatiche del database nella cartella "Backup", accanto al database
 *   (Documenti\Tab Manager\Backup): prima di ogni aggiornamento e una volta al giorno.
 *   Si tengono le ultime KEEP copie. Le fa il comando "php artisan tab:backup" (routes/console.php).
 * - Backup completo da scaricare (pulsante nell'elenco): uno ZIP con tutti i brani in .docx,
 *   il database e le istruzioni per il ripristino.
 *
 * Le copie del database si fanno con "VACUUM INTO" di SQLite: una copia coerente anche se
 * l'app è aperta e sta scrivendo, a differenza di una semplice copia del file.
 */
class TabBackup
{
    /**
     * Quante copie automatiche tenere nella cartella Backup (le più vecchie vengono cancellate).
     */
    public const KEEP = 30;

    public function __construct(private TabDocxGenerator $generator)
    {
    }

    /**
     * Percorso del database dei brani (DB_DATABASE nel file .env).
     */
    public function databasePath(): string
    {
        return (string) config('database.connections.sqlite.database');
    }

    /**
     * Cartella delle copie automatiche: "Backup", accanto al database.
     */
    public function folder(): string
    {
        return dirname($this->databasePath()).DIRECTORY_SEPARATOR.'Backup';
    }

    /**
     * Vero se oggi è già stata fatta almeno una copia.
     */
    public function hasToday(): bool
    {
        return glob($this->folder().DIRECTORY_SEPARATOR.now()->format('Y-m-d').' *.sqlite') !== [];
    }

    /**
     * Fa una copia datata del database nella cartella Backup, per esempio
     * "2026-11-25 17-05-12 prima dell'aggiornamento.sqlite", e toglie le copie più vecchie.
     * Restituisce il percorso della copia.
     */
    public function snapshot(string $label): string
    {
        if (! is_dir($this->folder()) && ! mkdir($this->folder(), 0777, true)) {
            throw new RuntimeException('Non riesco a creare la cartella '.$this->folder());
        }

        $path = $this->folder().DIRECTORY_SEPARATOR.now()->format('Y-m-d H-i-s').' '.$label.'.sqlite';
        $this->copyDatabase($path);
        $this->prune();

        return $path;
    }

    /**
     * Crea il backup completo da scaricare e ne restituisce il percorso (file temporaneo):
     *   Brani/Artista - Titolo.docx   tutti i brani, leggibili e stampabili anche senza l'app
     *   database.sqlite               l'archivio completo, da rimettere al suo posto per ripristinare
     *   LEGGIMI.txt                   cosa contiene e come si ripristina
     */
    public function zip(): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'tab-backup-');
        $databaseCopy = tempnam(sys_get_temp_dir(), 'tab-db-');
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Non riesco a creare il file ZIP.');
        }

        // I brani in .docx, con nomi diversi anche se due brani hanno lo stesso artista e titolo.
        $used = [];
        foreach (Song::query()->orderBy('artist')->orderBy('title')->get() as $song) {
            $name = pathinfo($this->generator->filename($song), PATHINFO_FILENAME);
            $file = $name;
            for ($n = 2; isset($used[mb_strtolower($file)]); $n++) {
                $file = "{$name} ({$n})";
            }
            $used[mb_strtolower($file)] = true;

            $docx = $this->generator->generate($song);
            $zip->addFromString("Brani/{$file}.docx", (string) file_get_contents($docx));
            @unlink($docx);
        }

        $this->copyDatabase($databaseCopy);
        $zip->addFromString('database.sqlite', (string) file_get_contents($databaseCopy));
        @unlink($databaseCopy);

        $zip->addFromString('LEGGIMI.txt', $this->readme(count($used)));
        $zip->close();

        return $zipPath;
    }

    /**
     * Nome del file ZIP da scaricare, per esempio "Tab Manager backup 2026-11-25 17-05.zip".
     */
    public function zipName(): string
    {
        return 'Tab Manager backup '.now()->format('Y-m-d H-i').'.zip';
    }

    /**
     * Copia coerente del database in $path (sovrascrive un file già esistente).
     */
    private function copyDatabase(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }

        DB::connection('sqlite')->statement('VACUUM INTO ?', [$path]);
    }

    /**
     * Tiene solo le ultime KEEP copie automatiche (i nomi iniziano con data e ora: in ordine
     * alfabetico sono anche in ordine di tempo).
     */
    private function prune(): void
    {
        $files = glob($this->folder().DIRECTORY_SEPARATOR.'*.sqlite') ?: [];
        rsort($files);

        foreach (array_slice($files, self::KEEP) as $old) {
            @unlink($old);
        }
    }

    /**
     * Testo del file LEGGIMI.txt dentro lo ZIP (righe con a capo di Windows, per il Blocco note).
     */
    private function readme(int $songs): string
    {
        $lines = [
            'Tab Manager - backup del '.now()->format('d/m/Y \a\l\l\e H:i'),
            '',
            "Contenuto:",
            "- cartella Brani: i {$songs} brani dell'archivio in formato Word (.docx), da aprire o stampare anche senza Tab Manager;",
            "- database.sqlite: l'archivio completo di Tab Manager, con tutta la formattazione.",
            '',
            'Per ripristinare i brani in Tab Manager (anche su un altro computer):',
            '1. chiudi Tab Manager;',
            '2. apri la cartella Documenti\\Tab Manager (se non c\'è, installa prima Tab Manager e aprilo una volta);',
            '3. copia qui il file database.sqlite di questo backup, al posto di quello che c\'è già;',
            '4. riapri Tab Manager.',
            '',
            "I .docx servono per leggere e stampare: Tab Manager non li sa rileggere. Per ripristinare serve database.sqlite.",
        ];

        return implode("\r\n", $lines)."\r\n";
    }
}
