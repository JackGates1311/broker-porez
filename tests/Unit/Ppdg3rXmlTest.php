<?php

namespace Tests\Unit;

use App\Models\PoreskiObaveznik;
use App\Services\Obrasci\Ppdg3rXml;
use App\Services\Porezi\Period;
use BcMath\Number;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class Ppdg3rXmlTest extends TestCase
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

    public function test_prodaja_sa_vise_lotova_je_jedan_unos_sa_vise_sticanja(): void
    {
        $x = $this->xpath(collect([
            $this->prodaja('AAPL', '2026-03-15', '10', '300000', [
                ['2025-01-10', '4', '100000'],
                ['2025-06-20', '6', '162000'],
                ['2025-07-01', '0.5', '13500.555'],
            ]),
            $this->prodaja('MSFT', '2026-04-02', '1', '50000', [['2025-02-03', '1', '45000']]),
        ]));

        $unosi = $x->query('/ns1:PodaciPoreskeDeklaracije/ns1:DeklarisanoPrenosHOVInvesticionihJed/ns1:PodaciOPrenosuHOVInvesticionihJed');
        $this->assertSame(2, $unosi->length);
        $this->assertSame('1', $unosi->item(0)->getAttribute('id'));
        $this->assertSame(3, $x->query('ns1:Sticanje', $unosi->item(0))->length);
        $this->assertSame(1, $x->query('ns1:Sticanje', $unosi->item(1))->length);

        $this->assertSame(
            ['RedniBroj', 'NazivEmitenta', 'DatumPrenosaHOV', 'BrojDokumentaOPrenosu', 'BrojPrenetihHOVInvesticionihJed', 'ProdajnaCena', 'Sticanje', 'Sticanje', 'Sticanje'],
            $this->deca($unosi->item(0)),
        );
        $this->assertSame(
            ['DatumSticanja', 'NabavnaCena', 'BrojDokumentaOSticanju', 'BrojStecenihHOVInvesticionihJed'],
            $this->deca($x->query('ns1:Sticanje', $unosi->item(0))->item(0)),
        );

        $this->assertSame('AAPL', $x->evaluate('string(ns1:NazivEmitenta)', $unosi->item(0)));
        $this->assertSame('2026-03-15', $x->evaluate('string(ns1:DatumPrenosaHOV)', $unosi->item(0)));
        $this->assertSame('10', $x->evaluate('string(ns1:BrojPrenetihHOVInvesticionihJed)', $unosi->item(0)));
        $this->assertSame('300000.00', $x->evaluate('string(ns1:ProdajnaCena)', $unosi->item(0)));
        $this->assertSame('2', $x->evaluate('string(ns1:RedniBroj)', $unosi->item(1)));

        $treci = $x->query('ns1:Sticanje', $unosi->item(0))->item(2);
        $this->assertSame('2025-07-01', $x->evaluate('string(ns1:DatumSticanja)', $treci));
        $this->assertSame('13500.56', $x->evaluate('string(ns1:NabavnaCena)', $treci));
        $this->assertSame('0.5', $x->evaluate('string(ns1:BrojStecenihHOVInvesticionihJed)', $treci));
    }

    public function test_podaci_o_prijavi_i_obavezniku(): void
    {
        $x = $this->xpath(collect([$this->prodaja('AAPL', '2026-03-15', '1', '1', [])]));

        $this->assertSame(
            ['PodaciOPrijavi', 'PodaciOPoreskomObvezniku', 'DeklarisanoPrenosHOVInvesticionihJed'],
            $this->deca($x->document->documentElement),
        );

        $prijava = '/ns1:PodaciPoreskeDeklaracije/ns1:PodaciOPrijavi/';
        $this->assertSame('1', $x->evaluate("string({$prijava}ns1:VrstaPrijave)"));
        $this->assertSame('4', $x->evaluate("string({$prijava}ns1:OsnovZaPrijavu)"));
        $this->assertSame('2026-06-30', $x->evaluate("string({$prijava}ns1:DatumOstvarenjaPrihodaDelaPrihoda)"));
        $this->assertSame('0', $x->evaluate("string({$prijava}ns1:IsplataUDelovima)"));
        $this->assertSame('2026-07-30', $x->evaluate("string({$prijava}ns1:DatumDospelostiZaPodnosenjePoreskePrijave)"));
        $this->assertSame('2026-07-10', $x->evaluate("string({$prijava}ns1:DatumINacinPodnosenjaPrijave/ns1:DatumPodnosenjaPrijave)"));
        $this->assertSame('E', $x->evaluate("string({$prijava}ns1:DatumINacinPodnosenjaPrijave/ns1:NacinPodnosenjaPrijave)"));

        $obaveznik = '/ns1:PodaciPoreskeDeklaracije/ns1:PodaciOPoreskomObvezniku/';
        $this->assertSame('1', $x->evaluate("string({$obaveznik}ns1:TipPoreskogObveznika)"));
        $this->assertSame('0101990710001', $x->evaluate("string({$obaveznik}ns1:PoreskiIdentifikacioniBroj)"));
        $this->assertSame('Petar & <Petrović>', $x->evaluate("string({$obaveznik}ns1:ImeIPrezimePoreskogObveznika)"));
        $this->assertSame('223', $x->evaluate("string({$obaveznik}ns1:PrebivalisteBoravistePoreskogObveznika)"));
        $this->assertSame('Bulevar ]]> 1', $x->evaluate("string({$obaveznik}ns1:AdresaPoreskogObveznika)"));
    }

    public function test_prepreke_navode_prodaje_bez_kursa_i_bez_kupovina(): void
    {
        $bezKursa = $this->prodaja('AAPL', '2026-03-15', '1', '1', []);
        $bezKursa->bez_kursa = true;
        $bezKupovina = $this->prodaja('MSFT', '2026-04-02', '5', '1', []);
        $bezKupovina->nedostaje = new Number('2.5');

        $prepreke = Ppdg3rXml::prepreke(collect([$bezKursa, $bezKupovina, $this->prodaja('IBM', '2026-04-03', '1', '1', [])]));

        $this->assertSame([
            'AAPL (15.03.2026): nedostaje NBS kurs prodaje ili neke od kupovina.',
            'MSFT (02.04.2026): nedostaju uvezene kupovine za 2,5 kom.',
        ], $prepreke);
    }

    private function xpath($prodaje): DOMXPath
    {
        $obaveznik = (new PoreskiObaveznik)->forceFill([
            'tip_obaveznika' => 1,
            'pib' => '0101990710001',
            'ime_prezime' => 'Petar & <Petrović>',
            'opstina_sifra' => '223',
            'adresa' => 'Bulevar ]]> 1',
            'telefon' => '0601234567',
            'email' => 'pera@example.com',
            'jmbg_podnosioca' => '0101990710001',
        ]);

        $xml = Ppdg3rXml::napravi(
            Period::za(2026, 1),
            $prodaje,
            $obaveznik,
            ['vrsta_prijave' => '1', 'osnov_za_prijavu' => '4'],
            CarbonImmutable::parse('2026-07-10 12:00', 'Europe/Belgrade'),
        );

        $dokument = new DOMDocument;
        $this->assertTrue($dokument->loadXML($xml));
        $x = new DOMXPath($dokument);
        $x->registerNamespace('ns1', 'http://pid.purs.gov.rs');

        return $x;
    }

    /**
     * @return list<string>
     */
    private function deca(\DOMNode $cvor): array
    {
        $imena = [];

        foreach ($cvor->childNodes as $dete) {
            if ($dete instanceof \DOMElement) {
                $this->assertSame('http://pid.purs.gov.rs', $dete->namespaceURI);
                $imena[] = $dete->localName;
            }
        }

        return $imena;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $lotovi  [datum, količina, nabavna RSD]
     */
    private function prodaja(string $simbol, string $datum, string $kolicina, string $vrednostRsd, array $lotovi): object
    {
        return (object) [
            'simbol' => $simbol,
            'naziv' => null,
            'vreme' => CarbonImmutable::parse("{$datum} 15:30", 'Europe/Belgrade'),
            'kolicina' => new Number($kolicina),
            'vrednost_rsd' => new Number($vrednostRsd),
            'bez_kursa' => false,
            'nedostaje' => null,
            'alokacije' => collect(array_map(fn ($l) => (object) [
                'vreme' => CarbonImmutable::parse("{$l[0]} 10:00", 'Europe/Belgrade'),
                'kolicina' => new Number($l[1]),
                'nabavna_rsd' => new Number($l[2]),
            ], $lotovi)),
        ];
    }
}
