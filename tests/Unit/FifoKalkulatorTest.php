<?php

namespace Tests\Unit;

use App\Enums\TipTransakcije;
use App\Services\Porezi\Dto\FifoTransakcija;
use App\Services\Porezi\FifoKalkulator;
use BcMath\Number;
use PHPUnit\Framework\TestCase;

class FifoKalkulatorTest extends TestCase
{
    private const TOLERANCIJA = '0.000001';

    /**
     * Svi redovi lista "Kapitalna Dobit" iz Excela: nabavna vrednost (kolona L, makro)
     * i "Preostalo u paketu" (kolona J) moraju se poklopiti.
     */
    public function test_reprodukuje_excel_list_kapitalna_dobit(): void
    {
        $redovi = $this->ucitajFixture();
        $transakcije = [];

        foreach ($redovi as $id => $red) {
            $transakcije[] = new FifoTransakcija(
                id: $id,
                imovinaId: $red['simbol'],
                tip: TipTransakcije::from($red['tip']),
                kolicina: new Number($red['kolicina']),
                cenaPoAkciji: new Number($red['cena']),
                kurs: new Number($red['kurs']),
            );
        }

        $rezultat = (new FifoKalkulator)->izracunaj($transakcije);
        $nabavna = FifoKalkulator::nabavnaPoProdaji($rezultat);
        $preostalo = [];

        foreach ($rezultat->lotovi as $lot) {
            $preostalo[$lot->transakcijaKupovineId] = $lot->preostalaKolicina;
        }

        $brojProdaja = 0;

        foreach ($redovi as $id => $red) {
            if ($red['tip'] === 'PRODAJA') {
                $brojProdaja++;
                $this->assertPribliznoJednako($red['ocekivana_nabavna_rsd'], $nabavna[$id] ?? new Number('0'), "Nabavna vrednost, red {$id} ({$red['simbol']})");
            } else {
                $this->assertPribliznoJednako($red['ocekivano_preostalo'], $preostalo[$id], "Preostalo u paketu, red {$id} ({$red['simbol']})");
            }
        }

        $this->assertSame(8, $brojProdaja);
        $this->assertSame([], $rezultat->nedostajuceKolicine);
    }

    public function test_prodaja_bez_dovoljno_kupovina_belezi_nedostajucu_kolicinu(): void
    {
        $rezultat = (new FifoKalkulator)->izracunaj([
            new FifoTransakcija(1, 7, TipTransakcije::Kupovina, new Number('1.5'), new Number('10'), new Number('100')),
            new FifoTransakcija(2, 7, TipTransakcije::Prodaja, new Number('2'), new Number('12'), new Number('101')),
        ]);

        $this->assertEquals(new Number('1500'), FifoKalkulator::nabavnaPoProdaji($rezultat)[2]);
        $this->assertEquals(new Number('0.5'), $rezultat->nedostajuceKolicine[2]);
        $this->assertEquals(new Number('0'), $rezultat->lotovi[0]->preostalaKolicina);
    }

    public function test_prodaja_trosi_najstariji_lot_iste_imovine(): void
    {
        $rezultat = (new FifoKalkulator)->izracunaj([
            new FifoTransakcija(1, 'A', TipTransakcije::Kupovina, new Number('1'), new Number('10'), new Number('100')),
            new FifoTransakcija(2, 'B', TipTransakcije::Kupovina, new Number('1'), new Number('99'), new Number('100')),
            new FifoTransakcija(3, 'A', TipTransakcije::Kupovina, new Number('1'), new Number('20'), new Number('100')),
            new FifoTransakcija(4, 'A', TipTransakcije::Prodaja, new Number('1.5'), new Number('30'), new Number('100')),
        ]);

        $this->assertCount(2, $rezultat->alokacije);
        $this->assertSame(1, $rezultat->alokacije[0]->transakcijaKupovineId);
        $this->assertSame(3, $rezultat->alokacije[1]->transakcijaKupovineId);
        $this->assertEquals(new Number('2000'), FifoKalkulator::nabavnaPoProdaji($rezultat)[4]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function ucitajFixture(): array
    {
        $fajl = fopen(__DIR__.'/../Fixtures/excel_kapitalna_dobit.csv', 'r');
        $zaglavlje = fgetcsv($fajl, escape: '');
        $redovi = [];
        $id = 1;

        while (($red = fgetcsv($fajl, escape: '')) !== false) {
            $redovi[$id++] = array_combine($zaglavlje, $red);
        }

        fclose($fajl);

        return $redovi;
    }

    private function assertPribliznoJednako(string $ocekivano, Number $stvarno, string $poruka): void
    {
        $razlika = $stvarno - new Number(sprintf('%.12F', (float) $ocekivano));

        $this->assertTrue(
            $razlika <= new Number(self::TOLERANCIJA) && $razlika >= new Number('-'.self::TOLERANCIJA),
            "{$poruka}: očekivano {$ocekivano}, dobijeno {$stvarno}",
        );
    }
}
