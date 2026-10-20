<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\DomenskeTabele;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class NalogTest extends TestCase
{
    use DomenskeTabele;
    use RefreshDatabase;

    private Korisnik $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->napraviDomenskeTabele();

        Schema::create('verifikacioni_kodovi', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->string('kod_hash');
            $tabela->timestamp('istice_at');
        });

        Schema::create('tokeni_reset_lozinke', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->string('token_hash', 64);
            $tabela->timestamp('istice_at');
        });

        $this->withoutVite();
        $this->korisnik = $this->spremanKorisnik();
    }

    private function dodajTransakciju(Korisnik $korisnik, string $kljuc): void
    {
        $imovinaId = DB::table('imovina')->insertGetId(['isin' => 'US'.str_pad($kljuc, 10, '0', STR_PAD_LEFT), 'simbol' => 'AAPL']);

        DB::table('transakcije')->insert([
            'korisnik_id' => $korisnik->id,
            'imovina_id' => $imovinaId,
            'tip_akcije' => 'Market buy',
            'tip' => 'KUPOVINA',
            'vreme_utc' => '2026-03-15 10:00:00',
            'kolicina' => '2',
            'cena_po_akciji' => '100',
            'valuta_cene' => 'USD',
            'ukupno' => '200',
            'valuta_ukupno' => 'USD',
            'jedinstveni_kljuc' => str_pad($kljuc, 64, '0'),
        ]);
    }

    public function test_meni_naloga_vodi_na_odjavu_i_pravne_strane(): void
    {
        $this->actingAs($this->korisnik)->get(route('pocetna'))
            ->assertOk()
            ->assertSee('data-bs-toggle="dropdown"', escape: false)
            ->assertSee(route('nalog'), escape: false)
            ->assertSee(route('pravno.politika'), escape: false)
            ->assertSee(route('pravno.uslovi'), escape: false)
            ->assertSee(route('odjava'), escape: false);
    }

    public function test_strana_naloga_je_dostupna_i_bez_popunjenog_obaveznika(): void
    {
        $this->korisnik->poreskiObaveznik()->delete();

        $this->actingAs($this->korisnik->fresh())->get(route('nalog'))
            ->assertOk()
            ->assertSee('Trajno obriši nalog')
            ->assertSee('Preuzmi moje podatke');
    }

    public function test_korisnicko_ime_se_menja_u_mom_nalogu(): void
    {
        $this->actingAs($this->korisnik)->get(route('nalog'))
            ->assertOk()
            ->assertSee('value="pera"', escape: false)
            ->assertSee('Prijava u dva koraka')
            ->assertSee('Uskoro');

        $this->spremanKorisnik('mika');
        $this->actingAs($this->korisnik)
            ->put(route('nalog.korisnicko-ime'), ['korisnicko_ime' => 'mika'])
            ->assertSessionHasErrors('korisnicko_ime');
        $this->actingAs($this->korisnik)
            ->put(route('nalog.korisnicko-ime'), ['korisnicko_ime' => 'pera ž'])
            ->assertSessionHasErrors('korisnicko_ime');

        // Isto ime nije "zauzeto" samim korisnikom.
        $this->actingAs($this->korisnik)
            ->put(route('nalog.korisnicko-ime'), ['korisnicko_ime' => 'pera'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->korisnik)
            ->put(route('nalog.korisnicko-ime'), ['korisnicko_ime' => ' petar_2 '])
            ->assertRedirect(route('nalog'))
            ->assertSessionHas('status', 'Korisničko ime je promenjeno.');
        $this->assertSame('petar_2', $this->korisnik->fresh()->korisnicko_ime);
    }

    public function test_osetljiva_polja_obaveznika_su_u_bazi_sifrovana(): void
    {
        $red = DB::table('poreski_obaveznik')->where('korisnik_id', $this->korisnik->id)->first();

        $this->assertNotSame('0101990710001', $red->pib);
        $this->assertSame('0101990710001', Crypt::decryptString($red->pib));
        $this->assertSame('265000000036005146', Crypt::decryptString($red->tekuci_racun));
        $this->assertSame('0101990710001', $this->korisnik->poreskiObaveznik()->first()->pib);
    }

    public function test_komanda_sifruje_stare_vrednosti_i_moze_ponovo(): void
    {
        DB::table('poreski_obaveznik')->where('korisnik_id', $this->korisnik->id)
            ->update(['pib' => '0101990710001', 'tekuci_racun' => '265000000036005146']);

        $this->artisan('obaveznik:sifruj')->expectsOutput('Šifrovano obaveznika: 1.')->assertSuccessful();
        $this->artisan('obaveznik:sifruj')->expectsOutput('Šifrovano obaveznika: 0.')->assertSuccessful();

        $obaveznik = $this->korisnik->poreskiObaveznik()->first();
        $this->assertSame('0101990710001', $obaveznik->pib);
        $this->assertSame('265000000036005146', $obaveznik->tekuci_racun);
        $this->assertSame('0101990710001', $obaveznik->jmbg_podnosioca);
    }

    public function test_izvoz_sadrzi_samo_podatke_korisnika(): void
    {
        $drugi = $this->spremanKorisnik('mika');
        $this->dodajTransakciju($this->korisnik, '1');
        $this->dodajTransakciju($drugi, '2');
        $this->korisnik->sabloniUvoza()->create(['naziv' => 'Moj broker', 'podesavanja' => ['separator' => ';']]);

        $odgovor = $this->actingAs($this->korisnik)->get(route('nalog.izvoz'))->assertOk();

        $this->assertStringContainsString('attachment; filename="brokerporez-moji-podaci-', $odgovor->headers->get('Content-Disposition'));
        $odgovor->assertJsonPath('nalog.email', 'pera@example.com')
            ->assertJsonPath('poreski_obaveznik.pib', '0101990710001')
            ->assertJsonPath('transakcije.0.isin', 'US0000000001')
            ->assertJsonCount(1, 'transakcije')
            ->assertJsonPath('sabloni_uvoza.0.podesavanja.separator', ';')
            ->assertJsonMissingPath('transakcije.0.korisnik_id');
    }

    public function test_pogresna_potvrda_ne_brise_nalog(): void
    {
        $this->actingAs($this->korisnik)
            ->delete(route('nalog.obrisi'), ['potvrda_emaila' => 'neko@example.com'])
            ->assertSessionHasErrors('potvrda_emaila');

        $this->assertNotNull(Korisnik::find($this->korisnik->id));
        $this->assertAuthenticated();
    }

    public function test_brisanje_uklanja_sve_podatke_i_ostavlja_samo_anonimni_zapis(): void
    {
        $drugi = $this->spremanKorisnik('mika');
        $this->dodajTransakciju($this->korisnik, '1');
        $this->dodajTransakciju($drugi, '2');
        $this->korisnik->sabloniUvoza()->create(['naziv' => 'Moj broker', 'podesavanja' => []]);
        $transakcijaId = DB::table('transakcije')->where('korisnik_id', $this->korisnik->id)->value('id');
        $lotId = DB::table('poreski_lotovi')->insertGetId([
            'korisnik_id' => $this->korisnik->id, 'transakcija_kupovine_id' => $transakcijaId, 'imovina_id' => 1,
            'pocetna_kolicina' => '2', 'preostala_kolicina' => '1', 'nabavna_cena_po_jedinici' => '10000',
        ]);
        DB::table('alokacije_lotova_prodaje')->insert([
            'korisnik_id' => $this->korisnik->id, 'transakcija_prodaje_id' => $transakcijaId, 'poreski_lot_id' => $lotId,
            'iskoriscena_kolicina' => '1', 'nabavna_vrednost_rsd' => '10000',
        ]);
        DB::table('verifikacioni_kodovi')->insert(['korisnik_id' => $this->korisnik->id, 'kod_hash' => 'x', 'istice_at' => now()]);
        DB::table('tokeni_reset_lozinke')->insert(['korisnik_id' => $this->korisnik->id, 'token_hash' => 'y', 'istice_at' => now()]);

        $this->actingAs($this->korisnik)
            ->delete(route('nalog.obrisi'), ['potvrda_emaila' => ' Pera@Example.com '])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'Vaš nalog i svi podaci su trajno obrisani.');

        $this->assertGuest();
        $this->assertNull(Korisnik::find($this->korisnik->id));

        foreach (['transakcije', 'poreski_lotovi', 'alokacije_lotova_prodaje', 'sabloni_uvoza', 'poreski_obaveznik', 'verifikacioni_kodovi', 'tokeni_reset_lozinke'] as $tabela) {
            $this->assertSame(0, DB::table($tabela)->where('korisnik_id', $this->korisnik->id)->count(), $tabela);
        }

        // Drugi korisnik ostaje netaknut.
        $this->assertSame(1, DB::table('transakcije')->where('korisnik_id', $drugi->id)->count());
        $this->assertNotNull($drugi->poreskiObaveznik()->first());

        $zapis = DB::table('obrisani_nalozi')->sole();
        $this->assertSame(now()->format('Y-m'), $zapis->registrovan_mesec);
        $this->assertSame('lozinka', $zapis->nacin_prijave);

        // Email je slobodan za novu registraciju.
        $this->assertFalse(Korisnik::where('email', 'pera@example.com')->exists());
    }
}
