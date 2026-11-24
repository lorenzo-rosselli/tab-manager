<?php

namespace App\Models;

use App\Enums\Instrument;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'artist', 'instrument', 'tuning', 'tab', 'notes'])]
class Song extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'instrument' => Instrument::class,
        ];
    }
}
