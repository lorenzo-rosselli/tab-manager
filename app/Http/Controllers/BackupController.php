<?php

namespace App\Http\Controllers;

use App\Services\TabBackup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pulsante "Backup" dell'elenco: scarica uno ZIP con tutti i brani in .docx e il database.
 * Un controller "invocabile": ha una sola azione, il metodo __invoke.
 */
class BackupController extends Controller
{
    public function __invoke(TabBackup $backup): BinaryFileResponse
    {
        return response()
            ->download($backup->zip(), $backup->zipName(), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
