<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use App\Models\SablonUvoza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\DomenskeTabele;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class UvozRucniTest extends TestCase
{
    use DomenskeTabele;
    use RefreshDatabase;

    private const CSV = <<<'CSV'
        Izvod brokera X
        Datum;Vrsta;ISIN;Papir;Kom;Cena;Val
        10.03.2026;KUPI;US0378331005;AAPL;2;100,50;USD
        12.03.2026;PRODAJ;US0378331005;AAPL;-1;120,00;USD
        13.03.2026;KAMATA;;;;;USD
        CSV;

    private Korisnik $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->napraviDomenskeTabele();
        $this->withoutVite();
        Storage::fake('local');
        // Kurs se ne preuzima u testu: transakcije ostaju bez kursa (kurs = NULL).
        Http::fake(['*' => Http::response('', 503)]);

        $this->korisnik = $this->spremanKorisnik();
    }

    public function test_ceo_carobnjak_uvozi_redove_sa_tipom_i_izvorom(): void
    {
        $this->zapocni()->assertRedirect(route('uvoz.rucni.format'));
        $this->assertCount(1, Storage::disk('local')->files('uvoz'));

        // Separator je prepoznat; pregled prikazuje sirove redove.
        $this->get(route('uvoz.rucni.format'))->assertOk()->assertSee('Izvod brokera X')->assertSee('Tačka-zarez ( ; )');

        $this->post(route('uvoz.rucni.format.sacuvaj'), $this->format(['dalje' => '1']))
            ->assertRedirect(route('uvoz.rucni.kolone'));

        $this->get(route('uvoz.rucni.kolone'))->assertOk()->assertSee('Vrsta');

        $this->post(route('uvoz.rucni.kolone.sacuvaj'), $this->kolone())->assertRedirect(route('uvoz.rucni.akcije'));

        $this->get(route('uvoz.rucni.akcije'))->assertOk()->assertSee('KUPI')->assertSee('KAMATA');

        $this->post(route('uvoz.rucni.akcije.sacuvaj'), $this->akcije())->assertRedirect(route('uvoz.rucni.pregled'));

        $this->get(route('uvoz.rucni.pregled'))->assertOk()
            ->assertSee('Ispravnih redova: <strong>3</strong>', false)
            ->assertSee('10.03.2026');

        $this->post(route('uvoz.rucni.uvezi'), ['sacuvaj_sablon' => '1', 'naziv_sablona' => 'Broker X'])
            ->assertRedirect(route('uvoz', ['izvor' => 'rucni']))
            ->assertSessionHas('status', fn (string $poruka) => str_contains($poruka, 'Uvezeno novih transakcija: 3') && str_contains($poruka, 'Šablon „Broker X” je sačuvan.'));

        $redovi = DB::table('transakcije')->orderBy('vreme_utc')->get();
        $this->assertSame(['KUPOVINA', 'PRODAJA', 'OSTALO'], $redovi->pluck('tip')->all());
        $this->assertSame(['RUCNI'], $redovi->pluck('izvor')->unique()->values()->all());
        $this->assertSame(['KUPI', 'PRODAJ', 'KAMATA'], $redovi->pluck('tip_akcije')->all());
        // 10.03.2026 00:00 po Beogradu (CET) = 09.03.2026 23:00 UTC.
        $this->assertSame('2026-03-09 23:00:00', (string) $redovi[0]->vreme_utc);
        $this->assertEquals(1, (float) $redovi[1]->kolicina);
        $this->assertSame('AAPL', DB::table('imovina')->where('isin', 'US0378331005')->value('simbol'));

        $this->assertSame([], Storage::disk('local')->files('uvoz'));
        $this->assertNull(session('uvoz_rucni'));
        $this->assertSame(1, SablonUvoza::query()->where('korisnik_id', $this->korisnik->id)->count());
    }

    public function test_ponovni_uvoz_po_sablonu_ne_pravi_duplikate(): void
    {
        $sablon = $this->sablon();

        $this->zapocni(['sablon_id' => $sablon->id])->assertRedirect(route('uvoz.rucni.pregled'));
        $this->post(route('uvoz.rucni.uvezi'))->assertSessionHas('status', fn ($p) => str_contains($p, 'Uvezeno novih transakcija: 3'));

        $this->zapocni(['sablon_id' => $sablon->id])->assertRedirect(route('uvoz.rucni.pregled'));
        $this->post(route('uvoz.rucni.uvezi'))->assertSessionHas('status', fn ($p) => str_contains($p, 'Uvezeno novih transakcija: 0 (pročitano 3, već postojalo 3)'));

        $this->assertSame(3, DB::table('transakcije')->count());
    }

    public function test_sablon_kome_fali_kolona_vraca_na_mapiranje_kolona(): void
    {
        $podesavanja = $this->sablon()->podesavanja;
        $podesavanja['kolone']['napomena'] = ['kolona' => 'Opis'];
        $sablon = $this->sablon($podesavanja, 'Sa napomenom');

        $this->zapocni(['sablon_id' => $sablon->id]);

        $this->get(route('uvoz.rucni.pregled'))->assertRedirect(route('uvoz.rucni.kolone'));
        $this->get(route('uvoz.rucni.kolone'))->assertOk()->assertSee('Fajl nema kolone iz šablona: <strong>Opis</strong>', false);
    }

    public function test_nova_vrednost_akcije_vraca_na_mapiranje_akcija(): void
    {
        $podesavanja = $this->sablon()->podesavanja;
        unset($podesavanja['akcije']['KAMATA']);
        $sablon = $this->sablon($podesavanja, 'Bez kamate');

        $this->zapocni(['sablon_id' => $sablon->id]);

        $this->get(route('uvoz.rucni.pregled'))->assertRedirect(route('uvoz.rucni.akcije'));
    }

    public function test_tudji_sablon_daje_404(): void
    {
        $tudji = $this->spremanKorisnik('mika')->sabloniUvoza()->create(['naziv' => 'Tuđi', 'podesavanja' => []]);

        $this->zapocni(['sablon_id' => $tudji->id])->assertNotFound();
        $this->delete(route('uvoz.sabloni.obrisi', $tudji->id))->assertNotFound();
        $this->assertSame(1, SablonUvoza::query()->count());
    }

    public function test_brisanje_sablona(): void
    {
        $sablon = $this->sablon();

        $this->actingAs($this->korisnik)->delete(route('uvoz.sabloni.obrisi', $sablon->id))
            ->assertRedirect(route('uvoz', ['izvor' => 'rucni']));

        $this->assertSame(0, SablonUvoza::query()->count());
    }

    public function test_odustani_brise_fajl_i_sesiju(): void
    {
        $this->zapocni();

        $this->post(route('uvoz.rucni.odustani'))->assertRedirect(route('uvoz', ['izvor' => 'rucni']));

        $this->assertSame([], Storage::disk('local')->files('uvoz'));
        $this->get(route('uvoz.rucni.format'))
            ->assertRedirect(route('uvoz', ['izvor' => 'rucni']))
            ->assertSessionHasErrors(['fajl_rucni' => 'Sesija uvoza je istekla, izaberite fajl ponovo.']);
    }

    public function test_kolone_traze_datum_i_akciju(): void
    {
        $this->zapocni();
        $this->post(route('uvoz.rucni.format.sacuvaj'), $this->format(['dalje' => '1']));

        $kolone = $this->kolone();
        $kolone['kolone']['akcija']['kolona'] = '';

        $this->post(route('uvoz.rucni.kolone.sacuvaj'), $kolone)->assertSessionHasErrors('kolone.akcija.kolona');
    }

    public function test_razmak_kao_separator_hiljada_se_cuva(): void
    {
        $this->zapocni();

        $this->post(route('uvoz.rucni.format.sacuvaj'), $this->format(['separator_hiljada' => 'razmak']))
            ->assertRedirect(route('uvoz.rucni.format'));

        $this->assertSame(' ', session('uvoz_rucni.podesavanja.separator_hiljada'));
        $this->assertSame(';', session('uvoz_rucni.podesavanja.separator'));
    }

    private function zapocni(array $podaci = []): TestResponse
    {
        return $this->actingAs($this->korisnik)->post(route('uvoz.rucni'), [
            'fajl_rucni' => UploadedFile::fake()->createWithContent('izvod.csv', self::CSV),
            ...$podaci,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function format(array $izmene = []): array
    {
        return [
            'separator' => ';',
            'kodna_strana' => 'UTF-8',
            'red_zaglavlja' => '2',
            'decimalni_separator' => ',',
            'separator_hiljada' => 'bez',
            ...$izmene,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kolone(): array
    {
        return [
            'format_datuma' => 'd.m.Y',
            'vremenska_zona' => 'Europe/Belgrade',
            'kolone' => [
                'vreme' => ['kolona' => 'Datum'],
                'akcija' => ['kolona' => 'Vrsta'],
                'isin' => ['kolona' => 'ISIN'],
                'simbol' => ['kolona' => 'Papir'],
                'kolicina' => ['kolona' => 'Kom'],
                'cena' => ['kolona' => 'Cena'],
                'valuta_cene' => ['kolona' => 'Val'],
                'valuta_ukupno' => ['kolona' => '', 'konstanta' => 'usd'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function akcije(): array
    {
        return ['akcije' => [
            ['vrednost' => 'KUPI', 'tip' => 'KUPOVINA'],
            ['vrednost' => 'PRODAJ', 'tip' => 'PRODAJA'],
            ['vrednost' => 'KAMATA', 'tip' => 'OSTALO'],
        ]];
    }

    /**
     * Šablon koji odgovara self::CSV.
     *
     * @param  array<string, mixed>|null  $podesavanja
     */
    private function sablon(?array $podesavanja = null, string $naziv = 'Broker X'): SablonUvoza
    {
        $podesavanja ??= [
            'separator' => ';',
            'red_zaglavlja' => 2,
            'decimalni_separator' => ',',
            'format_datuma' => 'd.m.Y',
            'vremenska_zona' => 'Europe/Belgrade',
            'kolone' => $this->kolone()['kolone'],
            'akcije' => ['KUPI' => 'KUPOVINA', 'PRODAJ' => 'PRODAJA', 'KAMATA' => 'OSTALO'],
        ];

        return $this->korisnik->sabloniUvoza()->firstOrCreate(['naziv' => $naziv], ['podesavanja' => $podesavanja]);
    }
}
