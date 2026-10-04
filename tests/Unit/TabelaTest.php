<?php

namespace Tests\Unit;

use App\Enums\TipTransakcije;
use App\Support\Tabela\Kolona;
use App\Support\Tabela\StanjeTabele;
use App\Support\Tabela\Tabela;
use App\Support\Tabela\TumacPretrage;
use PHPUnit\Framework\TestCase;

class TabelaTest extends TestCase
{
    public function test_brojevi_u_srpskom_i_tackastom_zapisu(): void
    {
        $this->assertSame([['1519.96', 2]], $this->brojevi('1.519,96'));
        $this->assertSame([['1519.9', 1]], $this->brojevi('1519,9'));
        $this->assertSame([['1519.96', 2]], $this->brojevi('1519.96'));
        $this->assertSame([['-12', 0]], $this->brojevi('-12'));
        // Dvosmisleno: hiljade ili decimale.
        $this->assertSame([['1519', 0], ['1.519', 3]], $this->brojevi('1.519'));
        $this->assertSame([], $this->brojevi('AAPL'));
        $this->assertSame([], $this->brojevi('15.03.2026'));
    }

    public function test_opseg_datuma_je_u_srpskoj_zoni_preracunat_u_utc(): void
    {
        $zona = 'Europe/Belgrade';

        // Mart 2026: pre prelaska na letnje vreme, UTC+1.
        $this->assertSame(['2026-03-14 23:00:00', '2026-03-15 23:00:00'], TumacPretrage::opsegDatuma('15.03.2026.', $zona));
        $this->assertSame(['2026-03-14 23:00:00', '2026-03-15 23:00:00'], TumacPretrage::opsegDatuma('2026-03-15', $zona));
        $this->assertSame(['2026-02-28 23:00:00', '2026-03-31 22:00:00'], TumacPretrage::opsegDatuma('03.2026', $zona));
        $this->assertSame(['2025-12-31 23:00:00', '2026-12-31 23:00:00'], TumacPretrage::opsegDatuma('2026', $zona));
        $this->assertNull(TumacPretrage::opsegDatuma('31.02.2026', $zona));
        $this->assertNull(TumacPretrage::opsegDatuma('13.2026', $zona));
        $this->assertNull(TumacPretrage::opsegDatuma('AAPL', $zona));
    }

    public function test_tekst_escape_uje_dzoker_znakove(): void
    {
        $uslovi = Kolona::tekst('simbol', 'Simbol')->izraz('i.simbol')->iTrazi('i.naziv')->usloviPretrage('10%_a!');

        $this->assertSame([
            ["i.simbol LIKE ? ESCAPE '!'", ['%10!%!_a!!%']],
            ["i.naziv LIKE ? ESCAPE '!'", ['%10!%!_a!!%']],
        ], $uslovi);
    }

    public function test_decimalna_pretraga_poredi_prikazanu_vrednost(): void
    {
        $kolona = Kolona::decimalni('iznos', 'Iznos')->izraz('t.iznos');

        $this->assertSame([[
            '(ROUND(t.iznos, 2) >= CAST(? AS DECIMAL(38,10)) AND ROUND(t.iznos, 2) < CAST(? AS DECIMAL(38,10)))',
            ['1519.9', '1520.0'],
        ]], $kolona->usloviPretrage('1.519,9'));

        $this->assertSame([[
            '(ROUND(t.iznos, 2) > CAST(? AS DECIMAL(38,10)) AND ROUND(t.iznos, 2) <= CAST(? AS DECIMAL(38,10)))',
            ['-13', '-12'],
        ]], $kolona->usloviPretrage('-12'));

        $this->assertSame([], $kolona->usloviPretrage('AAPL'));
    }

    public function test_ceo_broj_datum_i_enum(): void
    {
        $this->assertSame([['t.broj = ?', ['42']]], Kolona::ceo('broj', 'Broj')->izraz('t.broj')->usloviPretrage('42'));
        $this->assertSame([], Kolona::ceo('broj', 'Broj')->usloviPretrage('4,2'));

        $this->assertSame(
            [['(t.vreme >= ? AND t.vreme < ?)', ['2026-03-14 23:00:00', '2026-03-15 23:00:00']]],
            Kolona::datum('vreme', 'Vreme')->izraz('t.vreme')->usloviPretrage('15.03.2026'),
        );

        $tip = Kolona::enumeracija('tip', 'Tip')
            ->opcija('KUPOVINA', 'Kupovina', ...TipTransakcije::Kupovina->sqlUslov('t.tip'))
            ->opcija('PRODAJA', 'Prodaja', ...TipTransakcije::Prodaja->sqlUslov('t.tip'));

        $this->assertSame([['((t.tip LIKE ?))', ['% sell']]], $tip->usloviPretrage('prod'));
        $this->assertSame([], $tip->usloviPretrage('dividenda'));
        $this->assertSame(['CASE WHEN (t.tip LIKE ?) THEN 0 WHEN (t.tip LIKE ?) THEN 1 ELSE 2 END DESC', ['% buy', '% sell']], $tip->sortiranje('desc'));
    }

    public function test_sortiranje_stavlja_null_na_kraj(): void
    {
        $this->assertSame(
            ['CASE WHEN (t.kurs) IS NULL THEN 1 ELSE 0 END, t.kurs DESC', []],
            Kolona::decimalni('kurs', 'Kurs', 4)->izraz('t.kurs')->sortiranje('desc'),
        );
    }

    public function test_akcija_se_ne_sortira_i_ne_pretrazuje(): void
    {
        $akcija = Kolona::akcija('obrazac', 'Obrazac');

        $this->assertFalse($akcija->jeSortabilna());
        $this->assertSame([], $akcija->usloviPretrage('PP OPO'));
    }

    public function test_stanje_odbacuje_nepoznate_kolone_i_smerove(): void
    {
        $stanje = StanjeTabele::izUpita(
            ['q' => '  AAPL   inc ', 'sort' => 'vreme:desc,nepostoji:asc,simbol:gore,simbol:asc,vreme:asc'],
            ['vreme', 'simbol'],
            '/uvoz',
        );

        $this->assertSame('AAPL inc', $stanje->pretraga);
        $this->assertSame([['vreme', 'desc'], ['simbol', 'asc']], $stanje->sort);
        $this->assertTrue($stanje->jeIzmenjeno());
    }

    public function test_url_sortiranja_jedna_i_vise_kolona(): void
    {
        $tabela = $this->tabela(['period' => '2026', 'page' => '3']);

        // Bez korisničkog sorta važi podrazumevani (vreme:desc): klik na vreme obrće smer.
        $this->assertSame('/k?period=2026&sort=vreme%3Aasc', $tabela->urlSortiranja('vreme'));
        $this->assertSame('/k?period=2026&sort=simbol%3Aasc', $tabela->urlSortiranja('simbol'));
        $this->assertSame('/k?period=2026&sort=vreme%3Adesc%2Csimbol%3Aasc', $tabela->urlSortiranja('simbol', true));
        $this->assertSame([1, 'desc'], $tabela->prioritet('vreme'));

        $tabela = $this->tabela(['period' => '2026', 'q' => 'x', 'sort' => 'vreme:desc,simbol:asc']);

        $this->assertSame('/k?period=2026&q=x&sort=vreme%3Adesc%2Csimbol%3Adesc', $tabela->urlSortiranja('simbol', true));
        $this->assertSame('/k?period=2026&q=x&sort=simbol%3Aasc', $tabela->urlSortiranja('vreme', true));
        $this->assertSame('/k?period=2026&q=x&sort=simbol%3Aasc', $tabela->stanje()->urlBezSorta('vreme'));
        $this->assertSame([2, 'asc'], $tabela->prioritet('simbol'));
        $this->assertSame('/k?period=2026', $tabela->stanje()->urlReseta());
        $this->assertSame(['period' => '2026', 'sort' => 'vreme:desc,simbol:asc'], $tabela->stanje()->skrivenaPolja());
    }

    public function test_rezim_vise_kolona(): void
    {
        $tabela = $this->tabela(['period' => '2026', 'sort' => 'vreme:desc']);

        $this->assertFalse($tabela->stanje()->viseKolona);
        $this->assertSame('/k?period=2026&sort=simbol%3Aasc', $tabela->urlSortiranja('simbol'));
        $this->assertSame('/k?period=2026&sort=vreme%3Adesc&vise=1', $tabela->stanje()->urlViseKolona());

        $tabela = $this->tabela(['period' => '2026', 'sort' => 'vreme:desc', 'vise' => '1']);

        // U režimu više kolona običan klik na zaglavlje dodaje kolonu.
        $this->assertTrue($tabela->stanje()->viseKolona);
        $this->assertSame('/k?period=2026&vise=1&sort=vreme%3Adesc%2Csimbol%3Aasc', $tabela->urlSortiranja('simbol'));
        $this->assertSame('/k?period=2026&sort=vreme%3Adesc', $tabela->stanje()->urlViseKolona());
        $this->assertSame('/k?period=2026', $tabela->stanje()->urlReseta());
        $this->assertTrue($this->tabela(['vise' => '1'])->stanje()->jeIzmenjeno());
    }

    /**
     * @param  array<string, string>  $upit
     */
    private function tabela(array $upit): Tabela
    {
        return Tabela::od([
            Kolona::datum('vreme', 'Vreme'),
            Kolona::tekst('simbol', 'Simbol'),
            Kolona::akcija('obrazac', 'Obrazac'),
        ])->podrazumevano('vreme', 'desc')->izUpita($upit, '/k');
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function brojevi(string $tekst): array
    {
        return array_map(fn ($b) => [(string) $b[0], $b[1]], TumacPretrage::brojevi($tekst));
    }
}
