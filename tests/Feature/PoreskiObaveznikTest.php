<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use App\Models\PoreskiObaveznik;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\TestBaza;
use Tests\TestCase;

class PoreskiObaveznikTest extends TestCase
{
    use TestBaza;

    private Korisnik $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        // Middleware se proverava na probnoj ruti, bez stvarne strane knjige.
        Route::middleware(['web', 'auth', 'verifikovan', 'obaveznik'])->get('/proba-obaveznik', fn () => 'propusteno');

        $this->withoutVite();

        $this->korisnik = Korisnik::create([
            'korisnicko_ime' => 'pera',
            'email' => 'pera@example.com',
            'lozinka_hash' => 'Lozinka123',
        ]);
        $this->korisnik->forceFill(['email_verifikovan_at' => now()])->save();
    }

    /**
     * @return array<string, string>
     */
    private function ispravniPodaci(array $izmene = []): array
    {
        return [
            'tip_obaveznika' => '1',
            'pib' => '0101990710001',
            'ime_prezime' => 'Petar Petrović',
            'prebivaliste' => 'Novi Sad',
            'opstina_sifra' => 'Grad Novi Sad (223)',
            'adresa' => 'Bulevar oslobođenja 1',
            'telefon' => '0601234567',
            'email' => 'pera@example.com',
            'jmbg_podnosioca' => '0101990710001',
            'zemlja_rezidentstva' => 'DE',
            'pib_punomocnika' => '123456789',
            'tekuci_racun' => '265-0000000360051-46',
            'prihvatam_uslove' => '1',
            ...$izmene,
        ];
    }

    public function test_bez_podataka_korisnik_se_vodi_na_poreskog_obaveznika(): void
    {
        $this->actingAs($this->korisnik)
            ->get('/proba-obaveznik')
            ->assertRedirect(route('poreski-obaveznik'));

        $this->actingAs($this->korisnik)
            ->get(route('poreski-obaveznik'))
            ->assertOk()
            ->assertSee('Poreski obaveznik')
            ->assertSee('Pre korišćenja poreske knjige')
            ->assertSee('name="tip_obaveznika"', escape: false)
            ->assertSee('value="pera@example.com"', escape: false);
    }

    public function test_obavezna_polja(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), [])
            ->assertSessionHasErrors([
                'tip_obaveznika', 'ime_prezime', 'opstina_sifra', 'email', 'zemlja_rezidentstva',
            ])
            ->assertSessionDoesntHaveErrors(['pib', 'prebivaliste', 'adresa', 'telefon', 'jmbg_podnosioca', 'tekuci_racun', 'pib_punomocnika', 'broj_pasosa']);

        $this->assertNull($this->korisnik->poreskiObaveznik()->first());
    }

    public function test_tekuci_racun_se_cuva_kao_18_cifara_a_pasos_velikim_slovima(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tekuci_racun' => ' 265-360051-46 ', 'broj_pasosa' => 'ab 123456']))
            ->assertSessionHasNoErrors();

        $obaveznik = $this->korisnik->poreskiObaveznik()->first();
        $this->assertSame('265000000036005146', $obaveznik->tekuci_racun);
        $this->assertSame('AB123456', $obaveznik->broj_pasosa);

        // fresh(): korisnik iz prethodnog zahteva pamti relaciju poreskiObaveznik od pre čuvanja.
        $this->actingAs($this->korisnik->fresh())
            ->get(route('poreski-obaveznik'))
            ->assertSee('value="265-0000000360051-46"', escape: false);
    }

    public function test_tekuci_racun_mora_imati_ispravan_format_i_kontrolni_broj(): void
    {
        foreach (['265000000036005147', '265-36005146', '26500000003600514', 'racun', '265-12345678901234-46'] as $neispravan) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tekuci_racun' => $neispravan]))
                ->assertSessionHasErrors('tekuci_racun');
        }

        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['broj_pasosa' => '12-34']))
            ->assertSessionHasErrors('broj_pasosa');
    }

    public function test_telefon_sme_da_sadrzi_samo_cifre_i_separatore(): void
    {
        foreach (['06x1234567', 'telefon', '+381', '06 12', '381+641234567', '1234567890123456'] as $neispravan) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['telefon' => $neispravan]))
                ->assertSessionHasErrors('telefon');
        }

        foreach (['+381 64 123 4567', '064/123-45-67', '(011) 123.45.67', '0601234567'] as $ispravan) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['telefon' => $ispravan]))
                ->assertSessionDoesntHaveErrors('telefon');
        }
    }

    public function test_email_mora_biti_ispravna_adresa_sa_domenom(): void
    {
        foreach (['abc', 'a@b', 'ime@localhost', 'a@b.c', 'a@@b.com', 'a b@c.com', 'a@b..com', 'ime@primer.c0m'] as $neispravan) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['email' => $neispravan]))
                ->assertSessionHasErrors('email');
        }

        foreach (['pera@example.com', 'pera.peric@mail.co.rs', 'ime+porez@gmail.com'] as $ispravan) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['email' => $ispravan]))
                ->assertSessionDoesntHaveErrors('email');
        }
    }

    public function test_svaki_tip_mora_uneti_zemlju_rezidentstva(): void
    {
        foreach (['1', '3'] as $tip) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tip_obaveznika' => $tip, 'zemlja_rezidentstva' => '']))
                ->assertSessionHasErrors('zemlja_rezidentstva');
        }

        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tip_obaveznika' => '3', 'zemlja_rezidentstva' => 'Atlantida']))
            ->assertSessionHasErrors('zemlja_rezidentstva');
    }

    public function test_opstina_se_bira_iz_sifarnika(): void
    {
        foreach (['Novi Sad', '223', 'grad novi sad'] as $unos) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['opstina_sifra' => $unos]))
                ->assertSessionDoesntHaveErrors('opstina_sifra');
        }

        foreach (['', 'Atlantida', '999'] as $unos) {
            $this->actingAs($this->korisnik)
                ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['opstina_sifra' => $unos]))
                ->assertSessionHasErrors('opstina_sifra');
        }
    }

    public function test_prvi_unos_otkljucava_aplikaciju(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pocetna'));

        $obaveznik = $this->korisnik->poreskiObaveznik()->firstOrFail();
        $this->assertSame(1, $obaveznik->tip_obaveznika);
        $this->assertSame('223', $obaveznik->opstina_sifra);
        // Zemlja rezidentstva se čuva za svaki tip, a punomoćnik samo za tip 4.
        $this->assertSame('DE', $obaveznik->zemlja_rezidentstva);
        $this->assertNull($obaveznik->pib_punomocnika);
        $this->assertTrue($obaveznik->jePopunjen());

        $this->actingAs($this->korisnik->fresh())->get('/proba-obaveznik')->assertOk()->assertSee('propusteno');

        $this->actingAs($this->korisnik->fresh())
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['telefon' => '0611111111']))
            ->assertRedirect(route('poreski-obaveznik'));
    }

    public function test_osetljiva_polja_su_opciona_uz_upozorenje(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['pib' => '', 'prebivaliste' => '', 'adresa' => '', 'telefon' => '', 'jmbg_podnosioca' => '', 'tekuci_racun' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pocetna'));

        $obaveznik = $this->korisnik->poreskiObaveznik()->firstOrFail();
        $this->assertNull($obaveznik->pib);
        $this->assertNull($obaveznik->tekuci_racun);
        $this->assertNull($obaveznik->telefon);
        $this->assertNull($obaveznik->adresa);
        $this->assertTrue($obaveznik->jePopunjen());
        $this->assertSame(['2.2 poreski identifikacioni broj (JMBG/EBS/PIB)', '2.8 JMBG podnosioca prijave'], $obaveznik->nedostajeZaXml());

        $this->actingAs($this->korisnik->fresh())->get('/proba-obaveznik')->assertOk();
        $this->actingAs($this->korisnik->fresh())
            ->get(route('poreski-obaveznik'))
            ->assertSee('XML za „XML unos” na portalu ePorezi se ne pravi');
    }

    public function test_uslovi_se_prihvataju_pri_prvom_unosu(): void
    {
        $this->actingAs($this->korisnik)
            ->get(route('poreski-obaveznik'))
            ->assertSee('name="prihvatam_uslove"', escape: false)
            ->assertSee(route('pravno.politika'), escape: false)
            ->assertSee('data-bs-target="#modalUslovi"', escape: false)
            ->assertSee('id="modalPolitika"', escape: false)
            ->assertSee('Limited Use');

        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['prihvatam_uslove' => null]))
            ->assertSessionHasErrors('prihvatam_uslove');
        $this->assertNull($this->korisnik->poreskiObaveznik()->first());

        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci())
            ->assertSessionHasNoErrors();

        $korisnik = $this->korisnik->fresh();
        $this->assertSame(config('pravno.verzija'), $korisnik->uslovi_verzija);
        $this->assertNotNull($korisnik->uslovi_prihvaceni_at);

        // Prihvaćeni uslovi se ne traže ponovo pri izmeni podataka.
        $this->actingAs($korisnik)
            ->get(route('poreski-obaveznik'))
            ->assertDontSee('name="prihvatam_uslove"', escape: false)
            ->assertDontSee('id="modalUslovi"', escape: false);
        $this->actingAs($korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['prihvatam_uslove' => null]))
            ->assertSessionHasNoErrors();
    }

    public function test_nova_verzija_uslova_zakljucava_knjigu_do_prihvatanja(): void
    {
        $this->actingAs($this->korisnik)->put(route('poreski-obaveznik'), $this->ispravniPodaci());
        $korisnik = $this->korisnik->fresh();
        $this->actingAs($korisnik)->get('/proba-obaveznik')->assertOk();

        // Nova verzija koja je već stupila na snagu (ne zavisi od datuma u config/pravno.php).
        config(['pravno.verzija' => '2099-01-01', 'pravno.vazi_od' => now()->subDay()->toDateString()]);

        $this->actingAs($korisnik)->get('/proba-obaveznik')->assertRedirect(route('poreski-obaveznik'));
        $this->actingAs($korisnik)
            ->get(route('poreski-obaveznik'))
            ->assertSee('Uslovi korišćenja i Politika privatnosti su ažurirani')
            ->assertSee('name="prihvatam_uslove"', escape: false);

        $this->actingAs($korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci())
            ->assertRedirect(route('pocetna'));
        $this->actingAs($korisnik->fresh())->get('/proba-obaveznik')->assertOk();
    }

    public function test_nerezident_bez_boravista_cuva_punomocnika(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tip_obaveznika' => '4', 'zemlja_rezidentstva' => 'Nemačka (DE)']))
            ->assertSessionHasNoErrors();

        $obaveznik = $this->korisnik->poreskiObaveznik()->firstOrFail();
        $this->assertSame('DE', $obaveznik->zemlja_rezidentstva);
        $this->assertSame('123456789', $obaveznik->pib_punomocnika);
    }

    public function test_nepotpun_obaveznik_nije_popunjen(): void
    {
        $this->assertTrue((new PoreskiObaveznik($this->ispravniPodaci(['prebivaliste' => null, 'adresa' => null])))->jePopunjen());
        $this->assertFalse((new PoreskiObaveznik($this->ispravniPodaci(['opstina_sifra' => null])))->jePopunjen());
        $this->assertFalse((new PoreskiObaveznik($this->ispravniPodaci(['tip_obaveznika' => 3, 'zemlja_rezidentstva' => null])))->jePopunjen());
        $this->assertFalse((new PoreskiObaveznik($this->ispravniPodaci(['tip_obaveznika' => 1, 'zemlja_rezidentstva' => null])))->jePopunjen());
        $this->assertTrue((new PoreskiObaveznik($this->ispravniPodaci(['tip_obaveznika' => 1, 'zemlja_rezidentstva' => 'RS'])))->jePopunjen());
    }
}
