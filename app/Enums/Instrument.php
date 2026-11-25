<?php

namespace App\Enums;

enum Instrument: string
{
    case Bass = 'bass';
    case Guitar = 'guitar';
    case Voice = 'voice';

    /**
     * Nome dello strumento in italiano, da mostrare nelle pagine e nel .docx.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bass => 'Basso',
            self::Guitar => 'Chitarra',
            self::Voice => 'Voce',
        };
    }

    /**
     * Numeri di corde che si possono scegliere per lo strumento, ognuno con l'accordatura standard
     * dalla corda più acuta alla più grave (l'ordine delle righe di una tabulatura).
     * Il pulsante "Inserisci tabulatura" dell'editor scrive una riga per corda. La voce non ha corde.
     *
     * @return array<int, list<string>>
     */
    public function tunings(): array
    {
        return match ($this) {
            self::Bass => [
                4 => ['G', 'D', 'A', 'E'],
                5 => ['G', 'D', 'A', 'E', 'B'],
            ],
            self::Guitar => [
                6 => ['e', 'B', 'G', 'D', 'A', 'E'], // "e" minuscola: il Mi acuto, come si usa nelle tablature
            ],
            self::Voice => [],
        };
    }

    /**
     * Numero di corde proposto quando si sceglie lo strumento: il primo di tunings()
     * (basso 4, chitarra 6); null per la voce.
     */
    public function defaultStrings(): ?int
    {
        return array_key_first($this->tunings());
    }

    /**
     * Numeri di corde ammessi per lo strumento (es. [4, 5] per il basso).
     *
     * @return list<int>
     */
    public function strings(): array
    {
        return array_keys($this->tunings());
    }
}
