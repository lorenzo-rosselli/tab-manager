<?php

namespace Database\Seeders;

use App\Enums\Instrument;
use App\Models\Song;
use App\Services\TabDocument;
use Illuminate\Database\Seeder;

class SongSeeder extends Seeder
{
    /**
     * Inserisce alcuni brani di prova, con tabulature inventate.
     */
    public function run(): void
    {
        $this->song(
            title: 'Groove di prova in La',
            band: 'I Prova Generale',
            instrument: Instrument::Bass,
            strings: 4,
            showInstrument: true,
            showBand: true,
            text: <<<'TAB'
G|-----------------|-----------------|
D|-----------------|-----------------|
A|-----5---7-------|-----5---7---5---|
E|--5--------------|--5--------------|

G|-----------------|-----------------|
D|-----------5-7---|-----------7-5---|
A|--0---3-5--------|--0---3-5--------|
E|-----------------|-----------------|
TAB,
        );

        $this->song(
            title: 'Linea a cinque corde',
            band: null,
            instrument: Instrument::Bass,
            strings: 5,
            showInstrument: true,
            showBand: false,
            text: <<<'TAB'
Accordatura: B E A D G

G|-----------------|-----------------|
D|-----------------|-----------------|
A|-----------------|-----2---4-------|
E|-----2---4---5---|--5--------------|
B|--5--------------|-----------------|
TAB,
        );

        $this->song(
            title: 'Arpeggio di studio',
            band: 'Trio Acustico',
            instrument: Instrument::Guitar,
            strings: 6,
            showInstrument: false,
            showBand: true,
            text: <<<'TAB'
e|-------0---------|-------0---------|
B|-----1---1-------|-----3---3-------|
G|---0-------0-----|---2-------2-----|
D|-----------------|-----------------|
A|-3---------------|-----------------|
E|-----------------|-3---------------|
TAB,
        );
    }

    /**
     * Crea un brano: il foglio contiene le righe automatiche (artista - titolo, strumento, gruppo)
     * seguite da una riga vuota e dalla tabulatura.
     */
    private function song(string $title, ?string $band, Instrument $instrument, int $strings, bool $showInstrument, bool $showBand, string $text): void
    {
        $artist = 'Esercizi Tab Manager';

        Song::create([
            'title' => $title,
            'artist' => $artist,
            'band' => $band,
            'instrument' => $instrument,
            'strings' => $strings,
            'show_instrument' => $showInstrument,
            'show_band' => $showBand,
            'tab' => TabDocument::make(
                $artist,
                $title,
                $showInstrument ? $instrument->label() : null,
                $showBand ? $band : null,
                "\n".$text,
            ),
        ]);
    }
}
