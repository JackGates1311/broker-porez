<?php

namespace Tests\Unit;

use App\Services\Obrasci\RasporedDela4;
use PHPUnit\Framework\TestCase;

class RasporedDela4Test extends TestCase
{
    public function test_prodaja_sa_do_tri_lota_zauzima_jedan_blok(): void
    {
        $blokovi = RasporedDela4::blokovi(collect([$this->prodaja('A', 3), $this->prodaja('B', 1)]));

        $this->assertCount(2, $blokovi);
        $this->assertSame(1, $blokovi[0]->redni_broj);
        $this->assertSame(['A1', 'A2', 'A3'], $this->lotovi($blokovi[0]));
        $this->assertSame(2, $blokovi[1]->redni_broj);
        $this->assertSame(['B1', null, null], $this->lotovi($blokovi[1]));
    }

    public function test_cetvrti_lot_ide_u_sledeci_blok_bez_rednog_broja(): void
    {
        $prodaje = collect([
            ...array_map(fn ($i) => $this->prodaja("P{$i}", 1), range(1, 6)),
            $this->prodaja('IBM', 4),
            $this->prodaja('NVDA', 2),
        ]);

        $blokovi = RasporedDela4::blokovi($prodaje);

        $this->assertCount(9, $blokovi);
        $this->assertSame(7, $blokovi[6]->redni_broj);
        $this->assertSame(['IBM1', 'IBM2', 'IBM3'], $this->lotovi($blokovi[6]));
        $this->assertNull($blokovi[7]->redni_broj);
        $this->assertNull($blokovi[7]->prodaja);
        $this->assertSame(['IBM4', null, null], $this->lotovi($blokovi[7]));
        $this->assertSame(8, $blokovi[8]->redni_broj);
        $this->assertSame('NVDA', $blokovi[8]->prodaja->simbol);
    }

    public function test_sedam_lotova_zauzima_tri_bloka(): void
    {
        $blokovi = RasporedDela4::blokovi(collect([$this->prodaja('A', 7)]));

        $this->assertCount(3, $blokovi);
        $this->assertSame([1, null, null], array_map(fn ($b) => $b->redni_broj, $blokovi));
        $this->assertSame(['A7', null, null], $this->lotovi($blokovi[2]));
    }

    public function test_prodaja_bez_lotova_ima_prazne_redove_sticanja(): void
    {
        $blokovi = RasporedDela4::blokovi(collect([$this->prodaja('A', 0)]));

        $this->assertCount(1, $blokovi);
        $this->assertSame(1, $blokovi[0]->redni_broj);
        $this->assertSame([null, null, null], $blokovi[0]->stavke);
    }

    public function test_strane_imaju_po_devet_blokova(): void
    {
        $prodaje = collect(array_map(fn ($i) => $this->prodaja("P{$i}", 1), range(1, 10)));

        $strane = RasporedDela4::strane($prodaje);

        $this->assertCount(2, $strane);
        $this->assertCount(9, $strane[0]);
        $this->assertCount(9, $strane[1]);
        $this->assertSame(10, $strane[1][0]->redni_broj);
        $this->assertNull($strane[1][1]->prodaja);
    }

    public function test_bez_prodaja_postoji_jedna_prazna_strana(): void
    {
        $strane = RasporedDela4::strane(collect());

        $this->assertCount(1, $strane);
        $this->assertCount(9, $strane[0]);
        $this->assertNull($strane[0][0]->redni_broj);
    }

    private function prodaja(string $simbol, int $lotova): object
    {
        return (object) [
            'simbol' => $simbol,
            'alokacije' => collect(array_map(fn ($i) => (object) ['broker_id' => $simbol.$i], $lotova > 0 ? range(1, $lotova) : [])),
        ];
    }

    /**
     * @return list<?string>
     */
    private function lotovi(object $blok): array
    {
        return array_map(fn ($a) => $a?->broker_id, $blok->stavke);
    }
}
