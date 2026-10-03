<?php

namespace Tests\Unit;

use App\Enums\TipTransakcije;
use App\Services\Uvoz\Trading212CsvParser;
use App\Services\Uvoz\UvezeniRed;
use BcMath\Number;
use PHPUnit\Framework\TestCase;

class Trading212CsvParserTest extends TestCase
{
    /** @var list<string> */
    private array $fajlovi = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->fajlovi);
    }

    public function test_stari_format_sa_kolonom_time(): void
    {
        $redovi = $this->parsiraj(<<<'CSV'
            Action,Time,ISIN,Ticker,Name,No. of shares,Price / share,Currency (Price / share),Exchange rate,Total,Currency (Total),Withholding tax,Currency (Withholding tax)
            Dividend (Dividend),2025-09-10 12:12:59,US4592001014,IBM,"IBM",1.8096339300,1.176000,USD,1.00000000,2.13,"USD",0.91,USD
            CSV);

        $this->assertCount(1, $redovi);
        $red = $redovi[0];
        $this->assertSame(TipTransakcije::Dividenda, $red->tip);
        $this->assertSame('2025-09-10 12:12:59', $red->vremeUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2025-09-10', $red->datumSrb()->format('Y-m-d'));
        $this->assertSame('US4592001014', $red->isin);
        $this->assertEquals(new Number('1.80963393'), $red->kolicina);
        $this->assertEquals(new Number('0.91'), $red->porezPoOdbitku);
        $this->assertSame('USD', $red->valutaPoreza);
        $this->assertNull($red->brokerId);
    }

    public function test_novi_format_sa_utc_sufiksom_i_naknadama(): void
    {
        $redovi = $this->parsiraj(<<<'CSV'
            Action,Time (UTC),ISIN,Ticker,Name,Notes,ID,No. of shares,Price / share,Currency (Price / share),Exchange rate,Total,Currency (Total),Withholding tax,Currency (Withholding tax),Charge amount,Currency (Charge amount),Deposit fee,Currency (Deposit fee)
            Deposit,2026-07-15 05:43:03+00:00,,,,"Transaction ID: JR75",019f644c,,,,,150.00,"USD",,,151.05,"USD",-1.05,"USD"
            Market buy,2026-07-15 22:30:11+00:00,US4592001014,IBM,"IBM",,EOF54204930949,0.6820013500,220.8500000000,USD,1.00000000,150.62,"USD",,,,,,
            CSV);

        $this->assertSame(TipTransakcije::Depozit, $redovi[0]->tip);
        $this->assertEquals(new Number('1.05'), $redovi[0]->provizija, 'Charge amount nije naknada, Deposit fee jeste');
        $this->assertSame('USD', $redovi[0]->valutaProvizije);
        $this->assertNull($redovi[0]->isin);

        $this->assertSame(TipTransakcije::Kupovina, $redovi[1]->tip);
        $this->assertSame('EOF54204930949', $redovi[1]->brokerId);
        // 22:30 UTC je već sledeći dan po srpskom vremenu: kurs se uzima za 16.07.
        $this->assertSame('2026-07-16', $redovi[1]->datumSrb()->format('Y-m-d'));
    }

    public function test_isti_red_iz_razlicitih_formata_ima_isti_kljuc(): void
    {
        $a = $this->parsiraj(<<<'CSV'
            Action,Time,ISIN,Ticker,Name,No. of shares,Price / share,Currency (Price / share),Exchange rate,Total,Currency (Total),Withholding tax,Currency (Withholding tax)
            Dividend (Dividend),2026-04-24 15:46:33,AT0000652011,EBS,"Erste Group Bank",0.8574490800,0.543750,EUR,1.16813000,0.54,"USD",0.18,EUR
            CSV);
        $b = $this->parsiraj(<<<'CSV'
            Action,Time (UTC),ISIN,Ticker,Name,Notes,ID,No. of shares,Price / share,Currency (Price / share),Exchange rate,Total,Currency (Total),Withholding tax,Currency (Withholding tax)
            Dividend (Dividend),2026-04-24 15:46:33+00:00,AT0000652011,EBS,"Erste Group Bank",,,0.85744908,0.54375,EUR,1.16813000,0.54,"USD",0.18,EUR
            CSV);

        $this->assertSame($a[0]->jedinstveniKljuc, $b[0]->jedinstveniKljuc);
    }

    public function test_red_sa_pogresnim_brojem_kolona_se_preskace_uz_poruku(): void
    {
        $parser = new Trading212CsvParser;
        $redovi = $parser->parsiraj($this->fajl(<<<'CSV'
            Action,Time,ISIN,Ticker,Name,No. of shares,Price / share,Currency (Price / share),Exchange rate,Total,Currency (Total)
            Market sell,2026-05-08 17:19:09,US4581401001,INTC,"Intel",0.79713033,125.45,USD,1.00,100.00,"USD"
            Market sell,2026-05-08 17:19:09,US4581401001
            CSV), 'izvod.csv');

        $this->assertCount(1, $redovi);
        $this->assertSame(TipTransakcije::Prodaja, $redovi[0]->tip);
        $this->assertSame(['izvod.csv, red 3: broj kolona se ne poklapa sa zaglavljem.'], $parser->greske());
    }

    public function test_fajl_koji_nije_trading_212_izvod_se_odbija(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Trading212CsvParser)->parsiraj($this->fajl("a,b,c\n1,2,3"));
    }

    /**
     * @return list<UvezeniRed>
     */
    private function parsiraj(string $sadrzaj): array
    {
        $parser = new Trading212CsvParser;
        $redovi = $parser->parsiraj($this->fajl($sadrzaj));
        $this->assertSame([], $parser->greske());

        return $redovi;
    }

    private function fajl(string $sadrzaj): string
    {
        $putanja = tempnam(sys_get_temp_dir(), 't212');
        file_put_contents($putanja, "\xEF\xBB\xBF".$sadrzaj);

        return $this->fajlovi[] = $putanja;
    }
}
