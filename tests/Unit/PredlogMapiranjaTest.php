<?php

namespace Tests\Unit;

use App\Enums\TipTransakcije;
use App\Services\Uvoz\Rucni\PredlogMapiranja;
use PHPUnit\Framework\TestCase;

class PredlogMapiranjaTest extends TestCase
{
    public function test_kolone_po_nazivu_zaglavlja(): void
    {
        $predlog = (new PredlogMapiranja)->kolone([
            'Trade Date', 'Time (UTC)', 'Buy/Sell', 'ISIN', 'Symbol', 'Količina', 'Price', 'Currency', 'Commission', 'Nešto',
        ]);

        $this->assertSame([
            'vreme' => ['kolona' => 'Time (UTC)'],
            'akcija' => ['kolona' => 'Buy/Sell'],
            'isin' => ['kolona' => 'ISIN'],
            'simbol' => ['kolona' => 'Symbol'],
            'kolicina' => ['kolona' => 'Količina'],
            'cena' => ['kolona' => 'Price'],
            'valuta_cene' => ['kolona' => 'Currency'],
            'provizija' => ['kolona' => 'Commission'],
        ], $predlog);
    }

    public function test_jedna_kolona_se_ne_predlaze_za_dva_polja(): void
    {
        $predlog = (new PredlogMapiranja)->kolone(['Date', 'Action', 'Currency']);

        $this->assertSame(['kolona' => 'Currency'], $predlog['valuta_cene']);
        $this->assertArrayNotHasKey('valuta_ukupno', $predlog);
    }

    public function test_tip_po_kljucnim_recima(): void
    {
        $predlog = new PredlogMapiranja;

        $this->assertSame(TipTransakcije::Kupovina, $predlog->tip('Market buy'));
        $this->assertSame(TipTransakcije::Kupovina, $predlog->tip('BOUGHT'));
        $this->assertSame(TipTransakcije::Prodaja, $predlog->tip('Limit sell'));
        $this->assertSame(TipTransakcije::Prodaja, $predlog->tip('Prodaja'));
        $this->assertSame(TipTransakcije::Dividenda, $predlog->tip('Dividend (Ordinary)'));
        $this->assertSame(TipTransakcije::Dividenda, $predlog->tip('DIV'));
        $this->assertSame(TipTransakcije::Depozit, $predlog->tip('Uplata'));
        $this->assertSame(TipTransakcije::Ostalo, $predlog->tip('Interest on cash'));
    }
}
