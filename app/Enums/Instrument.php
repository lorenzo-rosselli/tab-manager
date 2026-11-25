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
}
