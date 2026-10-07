<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class RegistracijaTest extends TestCase
{
    use RefreshDatabase;

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

        $this->withoutVite();

        Korisnik::create([
            'korisnicko_ime' => 'pera',
            'email' => 'pera@example.com',
            'lozinka_hash' => 'lozinka123',
        ]);
        Korisnik::create([
            'korisnicko_ime' => 'pera'.now()->year,
            'email' => 'pera2@example.com',
            'lozinka_hash' => 'lozinka123',
        ]);
    }

    public function test_zauzeto_ime_vraca_slobodne_predloge(): void
    {
        $this->from(route('registracija'))
            ->post(route('registracija'), [
                'korisnicko_ime' => 'pera',
                'email' => 'novi@example.com',
                'lozinka' => 'lozinka123',
                'lozinka_confirmation' => 'lozinka123',
            ])
            ->assertRedirect(route('registracija'))
            ->assertSessionHasErrors(['korisnicko_ime' => 'Korisničko ime je već zauzeto.']);

        $predlozi = session('predlozi_korisnickog_imena');

        $this->assertCount(3, $predlozi);
        $this->assertNotContains('pera'.now()->year, $predlozi);

        foreach ($predlozi as $predlog) {
            $this->assertStringStartsWith('pera', $predlog);
            $this->assertFalse(Korisnik::where('korisnicko_ime', $predlog)->exists());
        }

        $this->get(route('registracija'))->assertSee('Slobodna imena:');
    }

    public function test_neispravno_ime_ne_dobija_predloge(): void
    {
        $this->post(route('registracija'), [
            'korisnicko_ime' => 'pera pera',
            'email' => 'novi@example.com',
            'lozinka' => 'lozinka123',
            'lozinka_confirmation' => 'lozinka123',
        ])->assertSessionHasErrors('korisnicko_ime');

        $this->assertNull(session('predlozi_korisnickog_imena'));
    }

    public function test_zauzet_email_ima_jasnu_poruku(): void
    {
        $this->post(route('registracija'), [
            'korisnicko_ime' => 'mika',
            'email' => 'Pera@Example.com',
            'lozinka' => 'lozinka123',
            'lozinka_confirmation' => 'lozinka123',
        ])->assertSessionHasErrors(['email' => 'Već postoji nalog sa ovom email adresom.']);

        $this->assertNull(session('predlozi_korisnickog_imena'));
    }
}
