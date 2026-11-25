<?php

namespace App\Models;

use App\Enums\Instrument;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'artist', 'band', 'instrument', 'strings', 'show_instrument', 'show_band', 'tab'])]
class Song extends Model
{
    /**
     * Font selezionabili nella barra: tutti monospaziati (caratteri della stessa larghezza),
     * così le colonne della tabulatura restano allineate.
     */
    public const FONTS = ['Lucida Console', 'Courier New', 'Consolas'];

    /**
     * Dimensioni selezionabili (in punti).
     */
    public const FONT_SIZES = [8, 9, 10, 11, 12, 14, 16, 18];

    /**
     * Allineamenti dei paragrafi: valore => nome mostrato.
     */
    public const ALIGNMENTS = ['left' => 'Allinea a sinistra', 'center' => 'Centra', 'right' => 'Allinea a destra'];

    /**
     * Tavolozza dei colori del testo nella barra (i "colori standard" di Word): valore => nome mostrato.
     * Senza colore il testo è "Automatico", cioè nero. Il testo incollato può avere anche altri colori:
     * il foglio ammette qualsiasi colore nella forma #RRGGBB (vedi TabDocument::paragraphs()).
     */
    public const TEXT_COLORS = [
        '#C00000' => 'Rosso scuro',
        '#FF0000' => 'Rosso',
        '#FFC000' => 'Arancione',
        '#00B050' => 'Verde',
        '#00B0F0' => 'Azzurro',
        '#0070C0' => 'Blu',
        '#002060' => 'Blu scuro',
        '#7030A0' => 'Viola',
        '#7F7F7F' => 'Grigio',
    ];

    /**
     * Colori dell'evidenziatore: valore => [nome mostrato, nome del colore di evidenziazione in Word].
     * Word ammette solo una tavolozza fissa di evidenziatori, quindi si usano quelli.
     */
    public const HIGHLIGHTS = [
        '#FFFF00' => ['Giallo', 'yellow'],
        '#00FF00' => ['Verde', 'green'],
        '#00FFFF' => ['Turchese', 'cyan'],
        '#FF00FF' => ['Rosa', 'magenta'],
        '#FF0000' => ['Rosso', 'red'],
        '#C0C0C0' => ['Grigio chiaro', 'lightGray'],
    ];

    /**
     * Formattazione del testo che non ne ha una propria.
     */
    public const DEFAULT_FONT = 'Lucida Console';

    public const DEFAULT_FONT_SIZE = 10;

    /**
     * Valori iniziali di un brano nuovo (gli stessi default della migration).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'show_instrument' => false,
        'show_band' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'instrument' => Instrument::class,
            'strings' => 'integer',
            'show_instrument' => 'boolean',
            'show_band' => 'boolean',
            'tab' => 'array', // il JSON del foglio diventa un array PHP (e viceversa)
        ];
    }
}
