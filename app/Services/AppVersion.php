<?php

namespace App\Services;

/**
 * Versione installata dell'app e titolo della finestra.
 *
 * La versione è il tag della Release di GitHub che l'aggiornamento automatico ha installato:
 * windows\update.ps1 lo scrive in windows/version.txt (es. "v1.0.1"). Il titolo della finestra è
 * sempre lo stesso in tutte le pagine: "Tab-Manager v.1.0.1".
 * Senza version.txt: sul computer di sviluppo (cartella con .git, dove non ci sono aggiornamenti
 * automatici) "Tab-Manager (sviluppo)", altrimenti solo "Tab-Manager" (per esempio prima del
 * primo aggiornamento di un'installazione nuova).
 */
class AppVersion
{
    public const NAME = 'Tab-Manager';

    /**
     * Numero di versione senza la "v" iniziale (es. "1.0.1"), oppure null se non si conosce.
     */
    public function number(): ?string
    {
        $file = base_path('windows/version.txt');

        if (! is_file($file)) {
            return null;
        }

        $version = ltrim(trim((string) file_get_contents($file)), 'vV');

        // Solo numeri e punti (es. "1.0.1", "1.2"): qualsiasi altra cosa non si mostra.
        return preg_match('/^\d+(\.\d+)*$/', $version) ? $version : null;
    }

    /**
     * Titolo della finestra, uguale in tutte le pagine.
     */
    public function title(): string
    {
        $version = $this->number();

        if ($version !== null) {
            return self::NAME.' v.'.$version;
        }

        return is_dir(base_path('.git')) ? self::NAME.' (sviluppo)' : self::NAME;
    }
}
