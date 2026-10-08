<?php

namespace Tests\Unit;

use App\Enums\TipTransakcije;
use App\Services\Uvoz\IzvorUvoza;
use App\Services\Uvoz\Rucni\CsvCitac;
use App\Services\Uvoz\Rucni\PodesavanjaUvoza;
use App\Services\Uvoz\Rucni\RucniCsvParser;
use App\Services\Uvoz\UvezeniRed;
use BcMath\Number;
use PHPUnit\Framework\TestCase;

class RucniCsvParserTest extends TestCase
{
    /** @var list<string> */
    private array $fajlovi = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->fajlovi);
    }

    public function test_evropski_format_sa_tacka_zarezom_i_beogradskim_vremenom(): void
    {
        $podesavanja = $this->podesavanja([
            'separator' => ';',
            'decimalni_separator' => ',',
            'separator_hiljada' => '.',
            'format_datuma' => 'd.m.Y H:i',
            'vremenska_zona' => 'Europe/Belgrade',
            'kolone' => ['simbol' => ['kolona' => 'Simbol']],
        ]);

        $redovi = $this->parsiraj($podesavanja, <<<'CSV'
            Datum;Tip;ISIN;Simbol;Kolicina;Cena;Valuta
            15.03.2026 00:30;KUPI;US0378331005;AAPL;1.234,5;210,25;USD
            CSV);

        $this->assertCount(1, $redovi);
        $red = $redovi[0];
        $this->assertSame(TipTransakcije::Kupovina, $red->tip);
        $this->assertSame(IzvorUvoza::Rucni, $red->izvor);
        $this->assertSame('KUPI', $red->akcija);
        // 00:30 po Beogradu (CET) je 23:30 UTC prethodnog dana; kurs se ipak uzima za 15.03.
        $this->assertSame('2026-03-14 23:30:00', $red->vremeUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-15', $red->datumSrb()->format('Y-m-d'));
        $this->assertEquals(new Number('1234.5'), $red->kolicina);
        $this->assertEquals(new Number('210.25'), $red->cenaPoAkciji);
        $this->assertEquals(new Number('259553.625'), $red->ukupno);
        $this->assertSame('USD', $red->valutaCene);
        $this->assertSame('USD', $red->valutaUkupno);
        $this->assertSame('AAPL', $red->simbol);
    }

    public function test_konstanta_valute_negativna_prodaja_i_tab_separator(): void
    {
        $podesavanja = $this->podesavanja([
            'separator' => "\t",
            'format_datuma' => 'Y-m-d',
            'kolone' => ['valuta_cene' => ['konstanta' => 'eur'], 'provizija' => ['kolona' => 'Fee']],
        ]);

        $redovi = $this->parsiraj($podesavanja, "Datum\tTip\tISIN\tKolicina\tCena\tFee\n2026-06-01\tSELL\tIE00B4L5Y983\t-3\t95.10\t-1.5\n");

        $red = $redovi[0];
        $this->assertSame(TipTransakcije::Prodaja, $red->tip);
        $this->assertSame('2026-06-01 00:00:00', $red->vremeUtc->format('Y-m-d H:i:s'));
        $this->assertEquals(new Number('3'), $red->kolicina);
        $this->assertSame('EUR', $red->valutaCene);
        $this->assertEquals(new Number('1.5'), $red->provizija);
        $this->assertSame('EUR', $red->valutaProvizije);
    }

    public function test_dividenda_samo_sa_iznosom_postaje_jedna_jedinica(): void
    {
        $podesavanja = $this->podesavanja([
            'kolone' => ['kolicina' => null, 'cena' => null, 'ukupno' => ['kolona' => 'Iznos'], 'porez' => ['kolona' => 'Porez']],
        ]);

        $redovi = $this->parsiraj($podesavanja, <<<'CSV'
            Datum,Tip,ISIN,Valuta,Iznos,Porez
            2026-05-10 12:00:00,DIV,US4592001014,USD,2.13,-0.91
            CSV);

        $red = $redovi[0];
        $this->assertSame(TipTransakcije::Dividenda, $red->tip);
        $this->assertEquals(new Number('1'), $red->kolicina);
        $this->assertEquals(new Number('2.13'), $red->cenaPoAkciji);
        $this->assertEquals(new Number('0.91'), $red->porezPoOdbitku);
        $this->assertSame('USD', $red->valutaPoreza);
    }

    public function test_depozit_bez_hartije(): void
    {
        $podesavanja = $this->podesavanja([
            'kolone' => ['kolicina' => null, 'cena' => null, 'ukupno' => ['kolona' => 'Iznos'], 'valuta_ukupno' => ['konstanta' => 'EUR']],
        ]);

        $red = $this->parsiraj($podesavanja, "Datum,Tip,ISIN,Valuta,Iznos\n2026-01-02 09:00:00,Uplata,,,500\n")[0];

        $this->assertSame(TipTransakcije::Depozit, $red->tip);
        $this->assertNull($red->isin);
        $this->assertNull($red->valutaCene);
        $this->assertEquals(new Number('500'), $red->ukupno);
        $this->assertSame('EUR', $red->valutaUkupno);
    }

    public function test_iso_format_sa_ofsetom(): void
    {
        $podesavanja = $this->podesavanja(['format_datuma' => 'iso', 'vremenska_zona' => 'America/New_York']);

        $red = $this->parsiraj($podesavanja, "Datum,Tip,ISIN,Kolicina,Cena,Valuta\n2026-03-15T14:30:00+01:00,BUY,US0378331005,1,1,USD\n")[0];

        $this->assertSame('2026-03-15 13:30:00', $red->vremeUtc->format('Y-m-d H:i:s'));
    }

    public function test_neispravni_redovi_se_preskacu_uz_poruku(): void
    {
        $parser = new RucniCsvParser($this->podesavanja());
        $redovi = $parser->parsiraj($this->fajl(<<<'CSV'
            Datum,Tip,ISIN,Kolicina,Cena,Valuta
            2026-03-15 10:00:00,BUY,US0378331005,1,100,USD
            15.03.2026,BUY,US0378331005,1,100,USD
            2026-03-15 10:00:00,TRANSFER,US0378331005,1,100,USD
            2026-03-15 10:00:00,BUY,,1,100,USD
            2026-03-15 10:00:00,BUY,US0378331005,abc,100,USD
            2026-03-15 10:00:00,BUY,US0378331005,1
            CSV), 'izvod.csv');

        $this->assertCount(1, $redovi);
        $this->assertSame([
            'izvod.csv, red 3: datum „15.03.2026” nije u formatu 2026-03-15 14:30:00.',
            'izvod.csv, red 4: akcija „TRANSFER” nije mapirana na tip.',
            'izvod.csv, red 5: nedostaje ISIN.',
            'izvod.csv, red 6: Količina: „abc” nije broj.',
            'izvod.csv, red 7: broj kolona se ne poklapa sa zaglavljem.',
        ], $parser->greske());
    }

    public function test_fajl_bez_mapiranih_kolona_se_odbija(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fajl izvod.csv nema kolone: Kolicina, Cena.');

        (new RucniCsvParser($this->podesavanja()))->parsiraj($this->fajl("Datum,Tip,ISIN,Valuta\n"), 'izvod.csv');
    }

    public function test_kljuc_je_stabilan_i_razlikuje_redove(): void
    {
        $csv = "Datum,Tip,ISIN,Kolicina,Cena,Valuta\n2026-03-15 10:00:00,BUY,US0378331005,1,100,USD\n2026-03-15 10:00:00,BUY,US0378331005,2,100,USD\n";

        $prvi = $this->parsiraj($this->podesavanja(), $csv);
        $drugi = $this->parsiraj($this->podesavanja(), $csv);

        $this->assertSame($prvi[0]->jedinstveniKljuc, $drugi[0]->jedinstveniKljuc);
        $this->assertNotSame($prvi[0]->jedinstveniKljuc, $prvi[1]->jedinstveniKljuc);
    }

    public function test_windows_1250_red_zaglavlja_i_duplirane_kolone(): void
    {
        $podesavanja = $this->podesavanja(['kodna_strana' => 'Windows-1250', 'red_zaglavlja' => 2]);
        $csv = iconv('UTF-8', 'Windows-1250', "Izvod brokera Šđčćž\nDatum,Tip,ISIN,Kolicina,Cena,Valuta,Naziv,Naziv\n2026-03-15 10:00:00,BUY,US0378331005,1,100,USD,Čelik,Žica\n");

        $citac = new CsvCitac;
        $procitano = $citac->procitaj($this->fajl($csv), $podesavanja);

        $this->assertSame(['Datum', 'Tip', 'ISIN', 'Kolicina', 'Cena', 'Valuta', 'Naziv', 'Naziv (2)'], $procitano['zaglavlje']);
        $this->assertSame([3], array_keys($procitano['redovi']));
        $this->assertSame('Čelik', $procitano['redovi'][3][6]);

        $red = $this->parsiraj($podesavanja->sa(['kolone' => [...$podesavanja->kolone, 'naziv' => ['kolona' => 'Naziv (2)']]]), $csv)[0];
        $this->assertSame('Žica', $red->naziv);
    }

    public function test_detekcija_separatora(): void
    {
        $citac = new CsvCitac;

        $this->assertSame(';', $citac->detektujSeparator($this->fajl("a;b;\"c,d\"\n1;2;3\n")));
        $this->assertSame("\t", $citac->detektujSeparator($this->fajl("a\tb\tc\n")));
        $this->assertSame(',', $citac->detektujSeparator($this->fajl("a,b\n")));
    }

    /**
     * Podrazumevano: zarez, datum Y-m-d H:i:s u UTC, kolone kao u testovima, BUY/SELL/DIV/KUPI/Uplata mapirani.
     *
     * @param  array<string, mixed>  $izmene
     */
    private function podesavanja(array $izmene = []): PodesavanjaUvoza
    {
        $kolone = [
            'vreme' => ['kolona' => 'Datum'],
            'akcija' => ['kolona' => 'Tip'],
            'isin' => ['kolona' => 'ISIN'],
            'kolicina' => ['kolona' => 'Kolicina'],
            'cena' => ['kolona' => 'Cena'],
            'valuta_cene' => ['kolona' => 'Valuta'],
        ];

        $osnova = [
            'kolone' => $kolone,
            'akcije' => ['BUY' => 'KUPOVINA', 'KUPI' => 'KUPOVINA', 'SELL' => 'PRODAJA', 'DIV' => 'DIVIDENDA', 'Uplata' => 'DEPOZIT'],
        ];

        // null u izmenama kolona uklanja podrazumevano mapiranje tog polja.
        if (isset($izmene['kolone'])) {
            $izmene['kolone'] = [...$kolone, ...$izmene['kolone']];
        }

        return PodesavanjaUvoza::izNiza([...$osnova, ...$izmene]);
    }

    /**
     * @return list<UvezeniRed>
     */
    private function parsiraj(PodesavanjaUvoza $podesavanja, string $csv): array
    {
        $parser = new RucniCsvParser($podesavanja);
        $redovi = $parser->parsiraj($this->fajl($csv), 'test.csv');
        $this->assertSame([], $parser->greske());

        return $redovi;
    }

    private function fajl(string $sadrzaj): string
    {
        $putanja = tempnam(sys_get_temp_dir(), 'rucni');
        file_put_contents($putanja, $sadrzaj);
        $this->fajlovi[] = $putanja;

        return $putanja;
    }
}
