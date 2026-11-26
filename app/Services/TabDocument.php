<?php

namespace App\Services;

use App\Models\Song;

/**
 * Il "foglio" della tabulatura: un documento formattato salvato nel formato JSON di Tiptap.
 *
 *   ['type' => 'doc', 'content' => [
 *       ['type' => 'paragraph', 'attrs' => ['textAlign' => 'center', 'auto' => 'header', 'autoText' => '...'],
 *        'content' => [['type' => 'text', 'text' => '...', 'marks' => [['type' => 'bold'], ...]]]],
 *       ...
 *   ]]
 *
 * Questa classe sa costruirlo (dati di prova), leggerlo in modo sicuro (solo ciò che l'app conosce)
 * e trasformarlo in HTML per l'anteprima. Il TabDocxGenerator lo usa per il Word.
 */
class TabDocument
{
    /**
     * Documento vuoto: un solo paragrafo senza testo.
     *
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
    }

    /**
     * Costruisce un foglio con le righe automatiche e il testo dato (una riga = un paragrafo).
     * Usato dal seeder; nel form lo stesso lavoro lo fa resources/js/tab-editor.js.
     *
     * @return array<string, mixed>
     */
    public static function make(string $artist, string $title, ?string $instrument, ?string $band, string $text): array
    {
        $content = [];

        $header = implode(' - ', array_filter([trim($artist), trim($title)]));
        if ($header !== '') {
            $content[] = self::autoParagraph('header', $header, 'bold', '16pt');
        }
        $info = self::infoLine($instrument, $band);
        if ($info !== '') {
            $content[] = self::autoParagraph('info', $info, 'italic', '8pt');
        }

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $content[] = $line === ''
                ? ['type' => 'paragraph']
                : ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $line]]];
        }

        return ['type' => 'doc', 'content' => $content];
    }

    /**
     * Seconda riga automatica: "Strumento: Basso - Nome del gruppo", oppure solo una delle due parti.
     * Stessa regola di resources/js/tab-editor.js.
     */
    public static function infoLine(?string $instrument, ?string $band): string
    {
        return implode(' - ', array_filter([
            filled($instrument) ? 'Strumento: '.trim($instrument) : null,
            filled($band) ? trim($band) : null,
        ]));
    }

    /**
     * Legge il documento e restituisce solo ciò che l'app conosce, già controllato:
     * una lista di paragrafi, ognuno con l'allineamento, i suoi pezzi di testo ("run") e pageBreakBefore
     * (vero se prima del paragrafo c'è un'interruzione di pagina: il paragrafo inizia su una pagina nuova).
     * Due interruzioni di fila danno una pagina vuota (un paragrafo vuoto su una pagina a sé);
     * un'interruzione in fondo al foglio, senza niente dopo, viene ignorata.
     * Font, dimensioni e colori non ammessi vengono ignorati (resta il default).
     * "font" e "size" del paragrafo (attributi fontFamily e fontSize, come il segno di paragrafo di Word)
     * contano solo per una riga vuota: danno la sua altezza, che altrimenti sarebbe quella del testo predefinito.
     *
     * @param  array<string, mixed>|null  $doc
     * @return list<array{align: string, pageBreakBefore: bool, font: ?string, size: ?int, runs: list<array{break?: true, text?: string, bold?: bool, italic?: bool, underline?: bool, font?: ?string, size?: ?int, color?: ?string, highlight?: ?string}>}>
     */
    public static function paragraphs(?array $doc): array
    {
        $paragraphs = [];
        $pageBreak = false; // c'è un'interruzione di pagina in attesa del paragrafo successivo

        foreach ($doc['content'] ?? [] as $node) {
            if (($node['type'] ?? null) === 'pageBreak') {
                if ($pageBreak) {
                    $paragraphs[] = ['align' => 'left', 'pageBreakBefore' => true, 'font' => null, 'size' => null, 'runs' => []];
                }
                $pageBreak = true;

                continue;
            }

            if (($node['type'] ?? null) !== 'paragraph') {
                continue;
            }

            $align = $node['attrs']['textAlign'] ?? 'left';
            $lineFont = $node['attrs']['fontFamily'] ?? null;
            $lineSize = (int) ($node['attrs']['fontSize'] ?? 0); // "12pt" → 12
            $runs = [];

            foreach ($node['content'] ?? [] as $child) {
                if (($child['type'] ?? null) === 'hardBreak') {
                    $runs[] = ['break' => true];

                    continue;
                }

                if (($child['type'] ?? null) !== 'text' || ! is_string($child['text'] ?? null)) {
                    continue;
                }

                $run = [
                    'text' => $child['text'],
                    'bold' => false,
                    'italic' => false,
                    'underline' => false,
                    'font' => null,
                    'size' => null,
                    'color' => null,      // es. "#C00000"; null = automatico (nero)
                    'highlight' => null,  // es. "#FFFF00"; null = nessuna evidenziazione
                ];

                foreach ($child['marks'] ?? [] as $mark) {
                    switch ($mark['type'] ?? null) {
                        case 'bold':
                        case 'italic':
                        case 'underline':
                            $run[$mark['type']] = true;
                            break;
                        case 'textStyle':
                            $font = $mark['attrs']['fontFamily'] ?? null;
                            $size = (int) ($mark['attrs']['fontSize'] ?? 0); // "14pt" → 14
                            $color = self::hexColor($mark['attrs']['color'] ?? null);
                            $run['font'] = in_array($font, Song::FONTS, true) ? $font : null;
                            $run['size'] = in_array($size, Song::FONT_SIZES, true) ? $size : null;
                            // Qualsiasi colore (anche incollato); il nero è "automatico".
                            $run['color'] = $color !== '#000000' ? $color : null;
                            break;
                        case 'highlight':
                            // Solo gli evidenziatori di Word (l'editor converte nel più vicino quelli incollati).
                            $highlight = self::hexColor($mark['attrs']['color'] ?? null);
                            $run['highlight'] = array_key_exists((string) $highlight, Song::HIGHLIGHTS) ? $highlight : null;
                            break;
                    }
                }

                $runs[] = $run;
            }

            $paragraphs[] = [
                'align' => array_key_exists((string) $align, Song::ALIGNMENTS) ? $align : 'left',
                'pageBreakBefore' => $pageBreak,
                'font' => in_array($lineFont, Song::FONTS, true) ? $lineFont : null,
                'size' => in_array($lineSize, Song::FONT_SIZES, true) ? $lineSize : null,
                'runs' => $runs,
            ];
            $pageBreak = false;
        }

        return $paragraphs;
    }

    /**
     * Vero se nel documento c'è almeno un carattere che non sia uno spazio.
     *
     * @param  array<string, mixed>|null  $doc
     */
    public static function hasText(?array $doc): bool
    {
        foreach (self::paragraphs($doc) as $paragraph) {
            foreach ($paragraph['runs'] as $run) {
                if (trim($run['text'] ?? '') !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * HTML del foglio per l'anteprima. Tutto il testo passa da e() (escaping), e negli stili
     * finiscono solo font, dimensioni e colori ammessi: l'HTML risultante è sicuro da stampare con {!! !!}.
     *
     * @param  array<string, mixed>|null  $doc
     */
    public static function toHtml(?array $doc): string
    {
        $html = '';

        foreach (self::paragraphs($doc) as $paragraph) {
            $inner = '';

            foreach ($paragraph['runs'] as $run) {
                if (isset($run['break'])) {
                    $inner .= '<br>';

                    continue;
                }

                $text = e($run['text']);
                $styles = array_filter([
                    $run['font'] ? "font-family: '{$run['font']}', monospace" : null,
                    $run['size'] ? "font-size: {$run['size']}pt" : null,
                    $run['color'] ? "color: {$run['color']}" : null,
                ]);

                if ($styles !== []) {
                    $text = '<span style="'.implode('; ', $styles).'">'.$text.'</span>';
                }
                if ($run['underline']) {
                    $text = "<u>{$text}</u>";
                }
                if ($run['italic']) {
                    $text = "<em>{$text}</em>";
                }
                if ($run['bold']) {
                    $text = "<strong>{$text}</strong>";
                }
                if ($run['highlight']) {
                    $text = '<mark style="background-color: '.$run['highlight'].'">'.$text.'</mark>';
                }

                $inner .= $text;
            }

            // L'interruzione di pagina è un <div> invisibile: resources/js/tab-pages.js lo usa per andare a pagina nuova.
            if ($paragraph['pageBreakBefore']) {
                $html .= '<div class="tab-page-break" data-page-break></div>';
            }

            // Un paragrafo vuoto resta una riga vuota, con il suo font e la sua dimensione (la sua altezza).
            $styles = ['text-align: '.$paragraph['align']];
            if ($inner === '') {
                $styles = array_merge($styles, array_filter([
                    $paragraph['font'] ? "font-family: '{$paragraph['font']}', monospace" : null,
                    $paragraph['size'] ? "font-size: {$paragraph['size']}pt" : null,
                ]));
            }
            $html .= '<p style="'.implode('; ', $styles).'">'.($inner === '' ? '<br>' : $inner).'</p>';
        }

        return $html;
    }

    /**
     * Colore nella forma "#RRGGBB" (maiuscolo), oppure null se il valore non è un colore valido.
     * Accetta "#RGB", "#RRGGBB" e "rgb(r, g, b)" (il formato con cui il browser legge i colori incollati);
     * un colore trasparente ("rgba(..., 0)") vale come nessun colore.
     */
    public static function hexColor(mixed $value): ?string
    {
        $value = strtolower(trim((string) (is_string($value) ? $value : '')));

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $value, $m)) {
            return strtoupper("#{$m[1]}{$m[1]}{$m[2]}{$m[2]}{$m[3]}{$m[3]}");
        }
        if (preg_match('/^#[0-9a-f]{6}$/', $value)) {
            return strtoupper($value);
        }
        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*([\d.]+)\s*)?\)$/', $value, $m)) {
            [$r, $g, $b] = [(int) $m[1], (int) $m[2], (int) $m[3]];

            if (max($r, $g, $b) > 255 || (isset($m[4]) && (float) $m[4] === 0.0)) {
                return null;
            }

            return sprintf('#%02X%02X%02X', $r, $g, $b);
        }

        return null;
    }

    /**
     * Paragrafo "automatico" ("Artista - Titolo" oppure la riga di strumento e gruppo): centrato, con un segno (grassetto o corsivo)
     * e una dimensione. "auto" dice di che riga si tratta, "autoText" il testo scritto in automatico:
     * finché il testo non cambia, l'editor può aggiornarlo quando cambiano i campi.
     *
     * @return array<string, mixed>
     */
    private static function autoParagraph(string $kind, string $text, string $mark, string $size): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => ['textAlign' => 'center', 'auto' => $kind, 'autoText' => $text],
            'content' => [[
                'type' => 'text',
                'text' => $text,
                'marks' => [['type' => $mark], ['type' => 'textStyle', 'attrs' => ['fontSize' => $size]]],
            ]],
        ];
    }
}
