<?php

namespace Tests\Unit;

use App\Models\PoreskiObaveznik;
use App\Models\Transakcija;
use App\Services\Obrasci\PpOpoXml;
use App\Services\Porezi\DividendaKalkulator;
use BcMath\Number;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class PpOpoXmlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Polja obaveznika su šifrovana ('encrypted' cast), a jedinični test nema aplikaciju ni APP_KEY.
        PoreskiObaveznik::encryptUsing(new Encrypter(Encrypter::generateKey('aes-256-cbc'), 'aes-256-cbc'));
    }

    protected function tearDown(): void
    {
        PoreskiObaveznik::encryptUsing(null);

        parent::tearDown();
    }

    public function test_prolazi_zvanicnu_semu(): void
    {
        $this->assertSame([], PpOpoXml::proveri($this->xml()));
        $this->assertSame([], PpOpoXml::proveri($this->xml(nacin: '3', punomocnik: '123456789', pasos: 'AB123456')));
    }

    public function test_bez_telefona_i_adrese_elementi_se_izostavljaju_i_prolazi_semu(): void
    {
        $xml = $this->xml(telefon: null, adresa: null);

        $this->assertSame([], PpOpoXml::proveri($xml));
        $this->assertStringNotContainsString('TelefonKontaktOsobe', $xml);
        $this->assertStringNotContainsString('UlicaBrojPoreskogObveznika', $xml);
        $this->assertStringContainsString('<ns1:TelefonKontaktOsobe>0601234567</ns1:TelefonKontaktOsobe>', $this->xml());
    }

    public function test_iznosi_su_zaokruzeni_i_za_uplatu_je_razlika_zaokruzenih(): void
    {
        $x = $this->xpath($this->xml());
        $prihod = '/ns1:PodaciPoreskeDeklaracije/ns1:DeklarisaniPodaciOVrstamaPrihoda/ns1:PodaciOVrstamaPrihoda/';

        // 10 × 3,4 USD + 6 USD porez = 40 USD bruto; × 102,1777 = 4.087,108 RSD.
        $this->assertSame('111402000', $x->evaluate("string({$prihod}ns1:SifraVrstePrihoda)"));
        $this->assertSame('4087.11', $x->evaluate("string({$prihod}ns1:BrutoPrihod)"));
        $this->assertSame('4087.11', $x->evaluate("string({$prihod}ns1:OsnovicaZaPorez)"));
        $this->assertSame('613.07', $x->evaluate("string({$prihod}ns1:ObracunatiPorez)"));
        $this->assertSame('613.07', $x->evaluate("string({$prihod}ns1:PorezPlacenDrugojDrzavi)"));
        $this->assertSame('0.00', $x->evaluate("string({$prihod}ns1:PorezZaUplatu)"));
        $this->assertSame('4087.11', $x->evaluate('string(/ns1:PodaciPoreskeDeklaracije/ns1:Ukupno/ns1:BrutoPrihod)'));
    }

    public function test_podaci_o_prijavi_i_nacin_ostvarivanja(): void
    {
        $x = $this->xpath($this->xml());
        $prijava = '/ns1:PodaciPoreskeDeklaracije/ns1:PodaciOPrijavi/';

        $this->assertSame('2026-03', $x->evaluate("string({$prijava}ns1:ObracunskiPeriod)"));
        $this->assertSame('2026-03-31', $x->evaluate("string({$prijava}ns1:DatumOstvarivanjaPrihoda)"));
        $this->assertSame('2026-04-30', $x->evaluate("string({$prijava}ns1:DatumDospelostiObaveze)"));
        $this->assertSame('RS', $x->evaluate('string(//ns1:DrzavaRezidenstva)'));
        $this->assertSame('265000000036005146', $x->evaluate('string(//ns1:PodaciONacinuOstvarivanjaPrihoda/ns1:TekuciRacun)'));
        $this->assertSame(0, $x->query('//ns1:Ostalo')->length);
        $this->assertSame(0, $x->query('//ns1:BrojPoreskogPunomocnika')->length);
        $this->assertSame(0, $x->query('//ns1:BrojPasosa')->length);

        $x = $this->xpath($this->xml(nacin: '3', pasos: 'AB123456'));
        $this->assertSame('Trading 212', $x->evaluate('string(//ns1:PodaciONacinuOstvarivanjaPrihoda/ns1:Ostalo)'));
        $this->assertSame(0, $x->query('//ns1:TekuciRacun')->length);
        $this->assertSame('AB123456', $x->evaluate('string(//ns1:PodaciOPoreskomObvezniku/ns1:BrojPasosa)'));
    }

    public function test_neispravni_podaci_obaveznika_ne_prolaze_semu(): void
    {
        $greske = PpOpoXml::proveri($this->xml(email: 'pera.example.com'));

        $this->assertNotSame([], $greske);
        $this->assertStringContainsString('ElektronskaPosta', $greske[0]);
        $this->assertStringNotContainsString('{http://pid.purs.gov.rs}', $greske[0]);
    }

    private function xml(string $nacin = '1', ?string $punomocnik = null, string $email = 'pera@example.com', ?string $pasos = null, ?string $telefon = '0601234567', ?string $adresa = 'Bulevar oslobođenja 1'): string
    {
        $obracun = (new DividendaKalkulator)->izracunaj(new Number('10'), new Number('3.4'), new Number('6'), new Number('102.1777'));

        $red = (object) [
            'transakcija' => (new Transakcija)->setRawAttributes(['izvor' => 'TRADING212']),
            'vreme' => CarbonImmutable::parse('2026-03-31 18:00', 'Europe/Belgrade'),
            'obracun' => $obracun,
        ];

        $obaveznik = (new PoreskiObaveznik)->forceFill([
            'tip_obaveznika' => 1,
            'pib' => '0101990710001',
            'ime_prezime' => 'Petar Petrović',
            'opstina_sifra' => '223',
            'adresa' => $adresa,
            'telefon' => $telefon,
            'email' => $email,
            'jmbg_podnosioca' => '0101990710001',
            'zemlja_rezidentstva' => 'RS',
            'pib_punomocnika' => $punomocnik,
            'tekuci_racun' => '265000000036005146',
            'broj_pasosa' => $pasos,
        ]);

        return PpOpoXml::napravi($red, $obaveznik, [
            'vrsta_prijave' => '1',
            'sifra_vrste_prihoda' => '111402000',
            'nacin_ostvarivanja' => $nacin,
        ]);
    }

    private function xpath(string $xml): DOMXPath
    {
        $dokument = new DOMDocument;
        $this->assertTrue($dokument->loadXML($xml));
        $x = new DOMXPath($dokument);
        $x->registerNamespace('ns1', 'http://pid.purs.gov.rs');

        return $x;
    }
}
