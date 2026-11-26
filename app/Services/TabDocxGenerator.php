<?php

namespace App\Services;

use App\Models\Song;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Crea il documento Word (.docx) di un brano: è esattamente il contenuto del suo foglio.
 */
class TabDocxGenerator
{
    /**
     * Genera il file .docx in una cartella temporanea e ne restituisce il percorso.
     */
    public function generate(Song $song): string
    {
        // Protegge i caratteri speciali dell'XML (& < >): senza, il file risulterebbe danneggiato.
        Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord;

        // Formattazione del testo che non ne ha una propria.
        $phpWord->setDefaultFontName(Song::DEFAULT_FONT);
        $phpWord->setDefaultFontSize(Song::DEFAULT_FONT_SIZE);

        // Pagina A4 con margini di 2 cm (PHPWord misura in "twip": 1 cm = 567 twip).
        $margin = Converter::cmToTwip(2);
        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => $margin,
            'marginBottom' => $margin,
            'marginLeft' => $margin,
            'marginRight' => $margin,
        ]);

        // Ogni paragrafo del foglio diventa un paragrafo Word (interlinea singola, nessuno spazio
        // prima/dopo); ogni pezzo di testo mantiene la sua formattazione. I paragrafi vuoti restano righe vuote.
        foreach (TabDocument::paragraphs($song->tab) as $paragraph) {
            $textRun = $section->addTextRun([
                'spaceBefore' => 0,
                'spaceAfter' => 0,
                'lineHeight' => 1.0,
                // Interruzione di pagina: il paragrafo inizia su una pagina nuova ("Anteponi interruzione di pagina").
                // Meglio di addPageBreak(), che lascerebbe una riga vuota in cima alla pagina nuova.
                'pageBreakBefore' => $paragraph['pageBreakBefore'],
                // LEFT/RIGHT sono i valori "classici" del formato .docx, letti da tutte le versioni di Word
                // (PHPWord li segna come deprecati a favore di START/END, meno compatibili).
                'alignment' => match ($paragraph['align']) {
                    'center' => Jc::CENTER,
                    'right' => Jc::RIGHT,
                    default => Jc::LEFT,
                },
            ]);

            // Riga vuota con un font o una dimensione: uno spazio con quel formato, così Word le dà la
            // stessa altezza che ha nel foglio (senza testo sarebbe alta come il testo predefinito).
            if ($paragraph['runs'] === [] && ($paragraph['font'] || $paragraph['size'])) {
                $textRun->addText(' ', [
                    'name' => $paragraph['font'] ?? Song::DEFAULT_FONT,
                    'size' => $paragraph['size'] ?? Song::DEFAULT_FONT_SIZE,
                ]);
            }

            foreach ($paragraph['runs'] as $run) {
                if (isset($run['break'])) {
                    $textRun->addTextBreak();

                    continue;
                }

                $textRun->addText($run['text'], [
                    'name' => $run['font'] ?? Song::DEFAULT_FONT,
                    'size' => $run['size'] ?? Song::DEFAULT_FONT_SIZE,
                    'bold' => $run['bold'],
                    'italic' => $run['italic'],
                    'underline' => $run['underline'] ? 'single' : 'none',
                    'color' => $run['color'] ? ltrim($run['color'], '#') : null,           // null = automatico (nero)
                    'fgColor' => $run['highlight'] ? Song::HIGHLIGHTS[$run['highlight']][1] : null, // evidenziatore di Word
                ]);
            }
        }

        // Salvataggio in un file temporaneo: il controller lo invia e poi lo cancella.
        $path = tempnam(sys_get_temp_dir(), 'tab_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    /**
     * Nome del file da scaricare: "Artista - Titolo.docx",
     * senza i caratteri che Windows non accetta nei nomi dei file.
     */
    public function filename(Song $song): string
    {
        $name = $song->artist.' - '.$song->title;
        $name = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return ($name !== '' ? $name : 'tabulatura').'.docx';
    }
}
