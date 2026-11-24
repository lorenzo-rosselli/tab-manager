<?php

namespace App\Services;

use App\Models\Song;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;

/**
 * Crea il documento Word (.docx) di un brano, impaginato come da specifiche (sezione 7).
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

        // Pagina A4 con margini di 2 cm (PHPWord misura in "twip": 1 cm = 567 twip).
        $margin = Converter::cmToTwip(2);
        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => $margin,
            'marginBottom' => $margin,
            'marginLeft' => $margin,
            'marginRight' => $margin,
        ]);

        // Intestazione: titolo, artista, riga info.
        $section->addText($song->title, ['size' => 18, 'bold' => true], ['spaceAfter' => 0]);
        $section->addText($song->artist, ['size' => 13, 'italic' => true], ['spaceAfter' => Converter::pointToTwip(6)]);
        $section->addText(
            'Strumento: '.$song->instrument->label().' – Accordatura: '.$song->tuning,
            ['size' => 10],
            ['spaceAfter' => Converter::pointToTwip(6)],
        );

        // Note (facoltative): una riga del testo = un paragrafo.
        if (filled($song->notes)) {
            foreach ($this->lines($song->notes) as $line) {
                $section->addText($line, ['size' => 10], ['spaceAfter' => 0]);
            }
            $section->addTextBreak();
        }

        // Tabulatura: Courier New 10 pt, una riga = un paragrafo, interlinea singola, nessuno spazio prima/dopo.
        // Le righe vuote restano paragrafi vuoti, così i blocchi restano separati.
        $tabFont = ['name' => 'Courier New', 'size' => 10];
        $tabParagraph = ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];

        foreach ($this->lines($song->tab) as $line) {
            $section->addText($line, $tabFont, $tabParagraph);
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

    /**
     * Divide un testo in righe, normalizzando gli a capo di Windows (\r\n) e quelli vecchi (\r).
     * Le righe vuote in fondo al testo vengono tolte; quelle in mezzo restano.
     *
     * @return list<string>
     */
    private function lines(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return explode("\n", rtrim($text, "\n"));
    }
}
