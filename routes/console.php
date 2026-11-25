<?php

use App\Services\TabBackup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Copia di sicurezza del database dei brani nella cartella Backup accanto al database
| (Documenti\Tab Manager\Backup). La lanciano gli script di Windows:
| - windows\setup.ps1 prima di ogni aggiornamento:  php artisan tab:backup --label="prima dell'aggiornamento"
| - windows\tab-manager.ps1 all'avvio dell'app:     php artisan tab:backup --daily  (solo se oggi non c'è già una copia)
| Si tengono le ultime 30 copie. A mano: php artisan tab:backup
*/
Artisan::command('tab:backup {--daily : Solo se oggi non è ancora stata fatta una copia} {--label=manuale : Parola aggiunta al nome della copia}', function (TabBackup $backup) {
    if (! is_file($backup->databasePath())) {
        $this->comment('Nessun database da copiare.');

        return;
    }

    if ($this->option('daily') && $backup->hasToday()) {
        $this->comment('Oggi c\'è già una copia.');

        return;
    }

    $label = $this->option('daily') ? 'automatico' : (string) $this->option('label');
    $this->info('Copia salvata: '.$backup->snapshot($label));
})->purpose('Copia di sicurezza del database dei brani');
