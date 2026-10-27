<?php

namespace Tests\Unit;

use App\Services\ZadovoljstvoIzvestaj;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ZadovoljstvoIzvestajTest extends TestCase
{
    public function test_sazetak_racuna_prosek_raspodelu_i_zadovoljne(): void
    {
        $sazetak = ZadovoljstvoIzvestaj::sazetak([
            ['ocena' => 5, 'povod' => 'ppdg-3r-xml'],
            ['ocena' => 4, 'povod' => 'ppdg-3r-xml'],
            ['ocena' => 2, 'povod' => 'pp-opo-pdf'],
        ]);

        $this->assertSame(3, $sazetak['broj']);
        $this->assertSame(3.67, $sazetak['prosek']);
        $this->assertSame([1 => 0, 2 => 1, 3 => 0, 4 => 1, 5 => 1], $sazetak['raspodela']);
        $this->assertSame(67, $sazetak['zadovoljnih']);
        // Redosled povoda je kao u OcenaZadovoljstva::POVODI, ne kao u odgovorima.
        $this->assertSame([
            'ppdg-3r-xml' => ['broj' => 2, 'prosek' => 4.5],
            'pp-opo-pdf' => ['broj' => 1, 'prosek' => 2.0],
        ], $sazetak['po_povodu']);
    }

    public function test_prazan_period_nema_proseka(): void
    {
        $sazetak = ZadovoljstvoIzvestaj::sazetak([]);

        $this->assertSame(0, $sazetak['broj']);
        $this->assertNull($sazetak['prosek']);
        $this->assertNull($sazetak['zadovoljnih']);
        $this->assertSame([], $sazetak['po_povodu']);
    }

    public function test_mesec_je_po_beogradskom_vremenu(): void
    {
        // 31.10. 23:30 UTC je u Beogradu već 1.11.
        $sada = CarbonImmutable::parse('2026-10-31 23:30:00', 'UTC');

        $this->assertSame('2026-10-01 00:00', ZadovoljstvoIzvestaj::mesec('prosli-mesec', $sada)->format('Y-m-d H:i'));
        $this->assertSame('2026-11-01 00:00', ZadovoljstvoIzvestaj::mesec('ovaj-mesec', $sada)->format('Y-m-d H:i'));
        $this->assertSame('Europe/Belgrade', ZadovoljstvoIzvestaj::mesec('2026-03')->getTimezone()->getName());
        $this->assertSame('2026-03-01', ZadovoljstvoIzvestaj::mesec('2026-03')->format('Y-m-d'));
    }

    public function test_naziv_meseca(): void
    {
        $this->assertSame('oktobar 2026. godine', ZadovoljstvoIzvestaj::nazivMeseca(CarbonImmutable::create(2026, 10, 1)));
        $this->assertSame('januar 2027. godine', ZadovoljstvoIzvestaj::nazivMeseca(CarbonImmutable::create(2027, 1, 1)));
        $this->assertSame('decembar 2026. godine', ZadovoljstvoIzvestaj::nazivMeseca(CarbonImmutable::create(2026, 12, 1)));
    }

    public function test_neispravan_period_baca_izuzetak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ZadovoljstvoIzvestaj::mesec('2026-13');
    }
}
