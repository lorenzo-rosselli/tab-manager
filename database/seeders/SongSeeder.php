<?php

namespace Database\Seeders;

use App\Enums\Instrument;
use App\Models\Song;
use Illuminate\Database\Seeder;

class SongSeeder extends Seeder
{
    /**
     * Inserisce alcuni brani di prova, con tabulature inventate.
     */
    public function run(): void
    {
        Song::create([
            'title' => 'Groove di prova in La',
            'artist' => 'Esercizi Tab Manager',
            'instrument' => Instrument::Bass,
            'tuning' => 'E A D G',
            'tab' => <<<'TAB'
G|-----------------|-----------------|
D|-----------------|-----------------|
A|-----5---7-------|-----5---7---5---|
E|--5--------------|--5--------------|

G|-----------------|-----------------|
D|-----------5-7---|-----------7-5---|
A|--0---3-5--------|--0---3-5--------|
E|-----------------|-----------------|
TAB,
            'notes' => 'Ripetere ogni blocco due volte.',
        ]);

        Song::create([
            'title' => 'Linea a cinque corde',
            'artist' => 'Esercizi Tab Manager',
            'instrument' => Instrument::Bass,
            'tuning' => 'B E A D G',
            'tab' => <<<'TAB'
G|-----------------|-----------------|
D|-----------------|-----------------|
A|-----------------|-----2---4-------|
E|-----2---4---5---|--5--------------|
B|--5--------------|-----------------|
TAB,
            'notes' => null,
        ]);

        Song::create([
            'title' => 'Arpeggio di studio',
            'artist' => 'Esercizi Tab Manager',
            'instrument' => Instrument::Guitar,
            'tuning' => 'E A D G B E',
            'tab' => <<<'TAB'
e|-------0---------|-------0---------|
B|-----1---1-------|-----3---3-------|
G|---0-------0-----|---2-------2-----|
D|-----------------|-----------------|
A|-3---------------|-----------------|
E|-----------------|-3---------------|
TAB,
            'notes' => 'Capotasto al 2° tasto.',
        ]);
    }
}
