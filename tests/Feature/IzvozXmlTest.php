<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use App\Models\Transakcija;
use App\Services\Obrasci\PpOpoXml;
use App\Services\Porezi\FifoService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\TestBaza;
use Tests\TestCase;

class IzvozXmlTest extends TestCase
{
    use TestBaza;

    private Korisnik $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->korisnik = $this->spremanKorisnik();
    }

    public function test_ppdg3r_xml_se_preuzima(): void
    {
        $this->transakcija('KUPOVINA', '2025-11-03 14:00:00', '3', '100', '110');
        $this->transakcija('KUPOVINA', '2026-01-05 14:00:00', '2', '120', '100');
        $this->transakcija('PRODAJA', '2026-03-16 14:00:00', '4', '150', '105');
        app(FifoService::class)->preracunaj($this->korisnik);

        $odgovor = $this->actingAs($this->korisnik)
            ->get(route('izvoz.ppdg-3r.xml', ['godina' => 2026, 'polugodiste' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="PPDG-3R_2026-H1.xml"');

        $xml = $odgovor->getContent();
        $this->assertStringContainsString('<ns1:DatumOstvarenjaPrihodaDelaPrihoda>2026-06-30</ns1:DatumOstvarenjaPrihodaDelaPrihoda>', $xml);
        $this->assertSame(1, substr_count($xml, '<ns1:PodaciOPrenosuHOVInvesticionihJed '));
        $this->assertSame(2, substr_count($xml, '<ns1:Sticanje>'));
        // 4 × 150 × 105 = 63.000; lotovi 3 × 100 × 110 = 33.000 i 1 × 120 × 100 = 12.000.
        $this->assertStringContainsString('<ns1:ProdajnaCena>63000.00</ns1:ProdajnaCena>', $xml);
        $this->assertStringContainsString('<ns1:NabavnaCena>33000.00</ns1:NabavnaCena>', $xml);
        $this->assertStringContainsString('<ns1:NabavnaCena>12000.00</ns1:NabavnaCena>', $xml);
        $this->assertStringContainsString('<ns1:PrebivalisteBoravistePoreskogObveznika>223</ns1:PrebivalisteBoravistePoreskogObveznika>', $xml);
    }

    public function test_ppdg3r_xml_se_odbija_za_prodaju_bez_kursa_ili_kupovina(): void
    {
        $this->transakcija('KUPOVINA', '2026-01-05 14:00:00', '1', '100', '100');
        $this->transakcija('PRODAJA', '2026-03-16 14:00:00', '3', '150', null);
        app(FifoService::class)->preracunaj($this->korisnik);

        $this->actingAs($this->korisnik)
            ->get(route('izvoz.ppdg-3r.xml', ['godina' => 2026, 'polugodiste' => 1]))
            ->assertRedirect(route('kapitalna-dobit', ['period' => '2026-H1']))
            ->assertSessionHasErrors('xml');

        $greske = session('errors')->get('xml');
        $this->assertContains('AAPL (16.03.2026): nedostaje NBS kurs prodaje ili neke od kupovina.', $greske);
        $this->assertContains('AAPL (16.03.2026): nedostaju uvezene kupovine za 2 kom.', $greske);

        // Poruka je u sesiji, pa se prati preusmerenje kao u pregledaču (novi zahtev bi počeo bez sesije).
        $this->actingAs($this->korisnik)
            ->followingRedirects()
            ->get(route('izvoz.ppdg-3r.xml', ['godina' => 2026, 'polugodiste' => 1]))
            ->assertOk()
            ->assertSee('nedostaju uvezene kupovine za 2 kom.')
            ->assertSee('Preuzmi PPDG-3R (XML za ePorezi)');
    }

    public function test_xml_se_ne_pravi_bez_jmbg_a_a_pdf_se_preuzima(): void
    {
        $this->transakcija('KUPOVINA', '2026-01-05 14:00:00', '1', '100', '100');
        $this->transakcija('PRODAJA', '2026-03-16 14:00:00', '1', '150', '105');
        app(FifoService::class)->preracunaj($this->korisnik);
        $this->korisnik->poreskiObaveznik->update(['pib' => null, 'jmbg_podnosioca' => null]);

        $this->actingAs($this->korisnik->fresh())
            ->get(route('izvoz.ppdg-3r.xml', ['godina' => 2026, 'polugodiste' => 1]))
            ->assertRedirect(route('kapitalna-dobit', ['period' => '2026-H1']))
            ->assertSessionHasErrors('xml');

        $this->assertStringContainsString('2.2 poreski identifikacioni broj (JMBG/EBS/PIB), 2.8 JMBG podnosioca prijave', implode(' ', session('errors')->get('xml')));

        $this->actingAs($this->korisnik->fresh())
            ->get(route('izvoz.ppdg-3r', ['godina' => 2026, 'polugodiste' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_ppdg3r_xml_bez_prodaja_u_polugodistu_se_odbija(): void
    {
        $this->actingAs($this->korisnik)
            ->get(route('izvoz.ppdg-3r.xml', ['godina' => 2026, 'polugodiste' => 2]))
            ->assertRedirect(route('kapitalna-dobit', ['period' => '2026-H2']))
            ->assertSessionHasErrors('xml');
    }

    public function test_pp_opo_xml_se_preuzima_i_prolazi_semu(): void
    {
        $id = $this->transakcija('DIVIDENDA', '2026-03-31 16:00:00', '10', '3.4', '102.1777', porez: '6');

        $odgovor = $this->actingAs($this->korisnik)
            ->get(route('izvoz.pp-opo.xml', ['transakcija' => $id, 'nacin_ostvarivanja' => 3]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="PP-OPO_2026-03-31_AAPL.xml"');

        $xml = $odgovor->getContent();
        $this->assertSame([], PpOpoXml::proveri($xml));
        $this->assertStringContainsString('<ns1:BrutoPrihod>4087.11</ns1:BrutoPrihod>', $xml);
        $this->assertStringContainsString('<ns1:NacinIsplate>3</ns1:NacinIsplate>', $xml);

        $this->get(route('dividende', ['period' => 2026]))
            ->assertOk()
            ->assertSee(route('izvoz.pp-opo.xml', $id), false);
    }

    public function test_pp_opo_xml_se_odbija_bez_kursa_ili_sa_neispravnim_emailom(): void
    {
        $bezKursa = $this->transakcija('DIVIDENDA', '2026-03-31 16:00:00', '10', '3.4', null);

        $this->actingAs($this->korisnik)
            ->get(route('izvoz.pp-opo.xml', $bezKursa))
            ->assertRedirect(route('dividende', ['period' => 2026]))
            ->assertSessionHasErrors('xml');

        $saKursom = $this->transakcija('DIVIDENDA', '2026-04-30 16:00:00', '10', '3.4', '100');
        $this->korisnik->poreskiObaveznik->update(['email' => 'pera@primer.срб']);

        $this->get(route('izvoz.pp-opo.xml', $saKursom))
            ->assertRedirect(route('dividende', ['period' => 2026]))
            ->assertSessionHasErrors('xml');
    }

    public function test_tudja_dividenda_nije_dostupna(): void
    {
        $id = $this->transakcija('DIVIDENDA', '2026-03-31 16:00:00', '10', '3.4', '100');

        $this->actingAs($this->spremanKorisnik('mika'))
            ->get(route('izvoz.pp-opo.xml', $id))
            ->assertNotFound();
    }

    private function transakcija(string $tip, string $vremeUtc, string $kolicina, string $cena, ?string $kurs, string $porez = '0'): int
    {
        $imovinaId = DB::table('imovina')->where('isin', 'US0378331005')->value('id')
            ?? DB::table('imovina')->insertGetId(['isin' => 'US0378331005', 'simbol' => 'AAPL', 'naziv' => 'Apple Inc.']);

        return Transakcija::query()->insertGetId([
            'korisnik_id' => $this->korisnik->id,
            'imovina_id' => $imovinaId,
            'tip_akcije' => $tip,
            'tip' => $tip,
            'izvor' => 'TRADING212',
            'vreme_utc' => $vremeUtc,
            'kolicina' => $kolicina,
            'cena_po_akciji' => $cena,
            'valuta_cene' => 'USD',
            'kurs' => $kurs,
            'ukupno' => '0',
            'valuta_ukupno' => 'USD',
            'porez_po_odbitku' => $porez,
            'valuta_poreza' => 'USD',
            'jedinstveni_kljuc' => hash('sha256', $tip.$vremeUtc.$kolicina),
        ]);
    }
}
