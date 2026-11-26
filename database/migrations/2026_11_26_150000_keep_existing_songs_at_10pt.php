<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * La dimensione predefinita del testo passa da 10 a 12 pt (Song::DEFAULT_FONT_SIZE, app.css,
 * tab-editor.js). I brani già salvati devono restare identici: al testo che non ha una dimensione
 * propria si scrive "10pt", e lo stesso alle righe vuote senza formato (attributo fontSize del
 * paragrafo). Così sullo schermo e nel Word non cambia niente; solo il testo nuovo parte da 12 pt.
 *
 * Si aggiorna solo la colonna "tab" con il query builder (DB::table), non con il modello Song:
 * updated_at non cambia, quindi la data e l'ordine dell'elenco restano quelli di prima.
 * Il JSON si legge come oggetti (non array), così gli oggetti vuoti restano {} quando si riscrive.
 */
return new class extends Migration
{
    private const OLD_SIZE = '10pt';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('songs')->select(['id', 'tab'])->orderBy('id')->each(function (object $song) {
            $doc = json_decode((string) $song->tab);

            if (! is_object($doc) || ! is_array($doc->content ?? null)) {
                return;
            }

            $changed = false;

            foreach ($doc->content as $node) {
                if (($node->type ?? null) !== 'paragraph') {
                    continue;
                }

                $children = is_array($node->content ?? null) ? $node->content : [];

                // Riga vuota senza formato: era alta come il testo a 10 pt.
                if ($children === [] && empty($node->attrs->fontSize)) {
                    $node->attrs ??= new stdClass;
                    $node->attrs->fontSize = self::OLD_SIZE;
                    $changed = true;
                }

                foreach ($children as $child) {
                    if (($child->type ?? null) !== 'text') {
                        continue;
                    }

                    $child->marks ??= [];
                    $style = null;
                    foreach ($child->marks as $mark) {
                        if (($mark->type ?? null) === 'textStyle') {
                            $style = $mark;
                        }
                    }

                    if ($style === null) {
                        $child->marks[] = (object) ['type' => 'textStyle', 'attrs' => (object) ['fontSize' => self::OLD_SIZE]];
                        $changed = true;
                    } elseif (empty($style->attrs->fontSize)) {
                        $style->attrs ??= new stdClass;
                        $style->attrs->fontSize = self::OLD_SIZE;
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                DB::table('songs')->where('id', $song->id)->update(['tab' => json_encode($doc)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Niente da annullare: non si può più distinguere il "10pt" scritto qui da quello scelto a mano.
    }
};
