<?php

namespace Tests\Unit;

use App\Support\TekuciRacun;
use PHPUnit\Framework\TestCase;

class TekuciRacunTest extends TestCase
{
    public function test_normalizuje_pun_skracen_i_zapis_bez_crtica(): void
    {
        $this->assertSame('265000000036005146', TekuciRacun::normalizuj('265-0000000360051-46'));
        $this->assertSame('265000000036005146', TekuciRacun::normalizuj(' 265-360051-46 '));
        $this->assertSame('265000000036005146', TekuciRacun::normalizuj('265000000036005146'));
        $this->assertNull(TekuciRacun::normalizuj('265-36005146'));
        $this->assertNull(TekuciRacun::normalizuj('265-12345678901234-46'));
        $this->assertNull(TekuciRacun::normalizuj(null));
    }

    public function test_kontrolni_broj_mod_97(): void
    {
        // Primer iz zvaničnog PPOPO.XML Poreske uprave.
        $this->assertTrue(TekuciRacun::kontrolniBrojJeIspravan('265000000036005146'));
        $this->assertFalse(TekuciRacun::kontrolniBrojJeIspravan('265000000036005147'));
        $this->assertFalse(TekuciRacun::kontrolniBrojJeIspravan('26500000003600514'));
    }

    public function test_prikaz_sa_crticama(): void
    {
        $this->assertSame('265-0000000360051-46', TekuciRacun::prikaz('265000000036005146'));
        $this->assertSame('', TekuciRacun::prikaz(null));
        $this->assertSame('265-36', TekuciRacun::prikaz('265-36'));
    }
}
