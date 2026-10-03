<?php

namespace Tests\Unit;

use App\Services\Porezi\DividendaKalkulator;
use BcMath\Number;
use PHPUnit\Framework\TestCase;

class DividendaKalkulatorTest extends TestCase
{
    /**
     * Svih 12 redova lista "Dividende" iz Excela (kolone J–O).
     */
    public function test_reprodukuje_excel_list_dividende(): void
    {
        $fajl = fopen(__DIR__.'/../Fixtures/excel_dividende.csv', 'r');
        $zaglavlje = fgetcsv($fajl, escape: '');
        $kalkulator = new DividendaKalkulator;
        $broj = 0;

        while (($red = fgetcsv($fajl, escape: '')) !== false) {
            $red = array_combine($zaglavlje, $red);
            $obracun = $kalkulator->izracunaj(
                new Number($red['kolicina']),
                new Number($red['neto_po_akciji']),
                new Number($red['porez_po_odbitku']),
                new Number($red['kurs']),
            );

            $opis = "{$red['datum']} {$red['simbol']}";
            $this->assertPriblizno($red['ocekivano_procenat'], $obracun->procenatPoreza, "{$opis} %");
            $this->assertPriblizno($red['ocekivano_bruto'], $obracun->bruto, "{$opis} bruto");
            $this->assertPriblizno($red['ocekivano_neto'], $obracun->neto, "{$opis} neto");
            $this->assertPriblizno($red['ocekivano_bruto_rsd'], $obracun->brutoRsd, "{$opis} bruto RSD");
            $this->assertPriblizno($red['ocekivano_porez_15'], $obracun->porez, "{$opis} porez 15%");
            $this->assertPriblizno($red['ocekivano_za_uplatu'], $obracun->zaUplatu, "{$opis} za uplatu");
            $broj++;
        }

        fclose($fajl);
        $this->assertSame(12, $broj);
    }

    public function test_bez_kursa_nema_rsd_vrednosti(): void
    {
        $obracun = (new DividendaKalkulator)->izracunaj(new Number('2'), new Number('1'), new Number('0.5'), null);

        $this->assertEquals(new Number('2.5'), $obracun->bruto);
        $this->assertNull($obracun->brutoRsd);
        $this->assertNull($obracun->zaUplatu);
    }

    private function assertPriblizno(string $ocekivano, ?Number $stvarno, string $poruka): void
    {
        $this->assertNotNull($stvarno, $poruka);
        $razlika = abs((float) (string) ($stvarno - new Number(sprintf('%.12F', (float) $ocekivano))));
        $this->assertLessThan(0.000001, $razlika, "{$poruka}: očekivano {$ocekivano}, dobijeno {$stvarno}");
    }
}
