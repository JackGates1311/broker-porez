<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use App\Models\PoreskiObaveznik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class PoreskiObaveznikTest extends TestCase
{
    use RefreshDatabase;

    private Korisnik $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        // Domenske tabele su u sql/, a ne u migracijama, pa ih test pravi sam.
        Schema::create('korisnici', function (Blueprint $tabela) {
            $tabela->increments('id');
            $tabela->string('korisnicko_ime', 50)->unique();
            $tabela->string('email', 100)->unique();
            $tabela->string('lozinka_hash');
            $tabela->timestamp('email_verifikovan_at')->nullable();
            $tabela->string('remember_token', 100)->nullable();
            $tabela->timestamp('kreirano_at')->useCurrent();
        });

        Schema::create('poreski_obaveznik', function (Blueprint $tabela) {
            $tabela->unsignedInteger('korisnik_id')->primary();
            $tabela->tinyInteger('tip_obaveznika')->nullable();
            $tabela->string('pib', 13)->nullable();
            $tabela->string('ime_prezime', 150)->nullable();
            $tabela->string('adresa')->nullable();
            $tabela->string('prebivaliste')->nullable();
            $tabela->string('telefon', 30)->nullable();
            $tabela->string('email', 100)->nullable();
            $tabela->char('jmbg_podnosioca', 13)->nullable();
            $tabela->string('zemlja_rezidentstva', 3)->nullable();
            $tabela->string('pib_punomocnika', 13)->nullable();
            $tabela->timestamp('azurirano_at')->nullable();
            $tabela->foreign('korisnik_id')->references('id')->on('korisnici')->cascadeOnDelete();
        });

        // Prava strana knjige traži domenske tabele, pa se middleware proverava na probnoj ruti.
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
            'adresa' => 'Bulevar oslobođenja 1',
            'telefon' => '0601234567',
            'email' => 'pera@example.com',
            'jmbg_podnosioca' => '0101990710001',
            'zemlja_rezidentstva' => 'DE',
            'pib_punomocnika' => '123456789',
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
                'tip_obaveznika', 'pib', 'ime_prezime', 'prebivaliste', 'adresa', 'telefon', 'email', 'jmbg_podnosioca',
            ])
            ->assertSessionDoesntHaveErrors(['zemlja_rezidentstva', 'pib_punomocnika']);

        $this->assertNull($this->korisnik->poreskiObaveznik()->first());
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

    public function test_nerezident_mora_uneti_zemlju_rezidentstva(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tip_obaveznika' => '3', 'zemlja_rezidentstva' => '']))
            ->assertSessionHasErrors('zemlja_rezidentstva');

        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['tip_obaveznika' => '3', 'zemlja_rezidentstva' => 'Atlantida']))
            ->assertSessionHasErrors('zemlja_rezidentstva');
    }

    public function test_prvi_unos_otkljucava_aplikaciju(): void
    {
        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pocetna'));

        $obaveznik = $this->korisnik->poreskiObaveznik()->firstOrFail();
        $this->assertSame(1, $obaveznik->tip_obaveznika);
        // Rezident nema zemlju rezidentstva ni punomoćnika.
        $this->assertNull($obaveznik->zemlja_rezidentstva);
        $this->assertNull($obaveznik->pib_punomocnika);
        $this->assertTrue($obaveznik->jePopunjen());

        $this->actingAs($this->korisnik)->get('/proba-obaveznik')->assertOk()->assertSee('propusteno');

        $this->actingAs($this->korisnik)
            ->put(route('poreski-obaveznik'), $this->ispravniPodaci(['telefon' => '0611111111']))
            ->assertRedirect(route('poreski-obaveznik'));
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
        $this->assertFalse((new PoreskiObaveznik($this->ispravniPodaci(['prebivaliste' => null])))->jePopunjen());
        $this->assertFalse((new PoreskiObaveznik($this->ispravniPodaci(['tip_obaveznika' => 3, 'zemlja_rezidentstva' => null])))->jePopunjen());
        $this->assertTrue((new PoreskiObaveznik($this->ispravniPodaci(['tip_obaveznika' => 1, 'zemlja_rezidentstva' => null])))->jePopunjen());
    }
}
