<?php

namespace Tests\Feature;

use App\Mail\ResetLozinkeMail;
use App\Models\Korisnik;
use App\Services\ResetLozinkeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteKorisnik;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\DomenskeTabele;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class GooglePrijavaTest extends TestCase
{
    use DomenskeTabele;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->napraviDomenskeTabele();
        $this->withoutVite();
    }

    private function googleVraca(string $email, string $id = 'g-123', bool $potvrdjen = true): void
    {
        $profil = (new SocialiteKorisnik)
            ->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $potvrdjen])
            ->map(['id' => $id, 'email' => $email, 'name' => 'Pera Perić']);

        Socialite::shouldReceive('driver->user')->andReturn($profil);
    }

    public function test_prijava_prikazuje_google_dugme(): void
    {
        $this->get(route('prijava'))
            ->assertOk()
            ->assertSee(route('prijava.google'), escape: false)
            ->assertSee('Prijavi se preko Google-a');
    }

    public function test_dugme_preusmerava_na_google(): void
    {
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'tajna']);

        $odgovor = $this->get(route('prijava.google'));

        $odgovor->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $odgovor->headers->get('Location'));
        $this->assertStringContainsString('prompt=select_account', $odgovor->headers->get('Location'));
    }

    public function test_nov_korisnik_dobija_verifikovan_nalog(): void
    {
        $this->googleVraca('Pera.Peric@gmail.com');

        $this->get(route('prijava.google.povratak'))->assertRedirect(route('pocetna', absolute: false));

        $korisnik = Korisnik::where('email', 'pera.peric@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($korisnik);
        $this->assertSame('g-123', $korisnik->google_id);
        $this->assertSame('pera_peric', $korisnik->korisnicko_ime);
        $this->assertTrue($korisnik->jeVerifikovan());
    }

    public function test_zauzeto_korisnicko_ime_se_menja_automatski(): void
    {
        Korisnik::create(['korisnicko_ime' => 'pera', 'email' => 'drugi@example.com', 'lozinka_hash' => 'lozinka123']);
        $this->googleVraca('pera@gmail.com');

        $this->get(route('prijava.google.povratak'));

        $ime = Korisnik::where('email', 'pera@gmail.com')->value('korisnicko_ime');
        $this->assertNotSame('pera', $ime);
        $this->assertStringStartsWith('pera', $ime);
    }

    public function test_postojeci_verifikovan_nalog_se_povezuje_i_zadrzava_lozinku(): void
    {
        $korisnik = $this->spremanKorisnik();
        $hes = $korisnik->lozinka_hash;
        $this->googleVraca($korisnik->email);

        $this->get(route('prijava.google.povratak'));

        $korisnik->refresh();
        $this->assertAuthenticatedAs($korisnik);
        $this->assertSame('g-123', $korisnik->google_id);
        $this->assertSame($hes, $korisnik->lozinka_hash);
        $this->assertSame(1, Korisnik::count());
    }

    public function test_neverifikovan_nalog_gubi_staru_lozinku(): void
    {
        $korisnik = Korisnik::create(['korisnicko_ime' => 'uljez', 'email' => 'zrtva@gmail.com', 'lozinka_hash' => 'uljezova1']);
        $this->googleVraca('zrtva@gmail.com');

        $this->get(route('prijava.google.povratak'));

        $korisnik->refresh();
        $this->assertAuthenticatedAs($korisnik);
        $this->assertTrue($korisnik->jeVerifikovan());
        $this->assertFalse(Hash::check('uljezova1', $korisnik->lozinka_hash));
    }

    public function test_nalog_se_nalazi_po_google_id_posle_promene_emaila(): void
    {
        $korisnik = $this->spremanKorisnik();
        $korisnik->forceFill(['google_id' => 'g-123'])->save();
        $this->googleVraca('nova.adresa@gmail.com');

        $this->get(route('prijava.google.povratak'));

        $this->assertAuthenticatedAs($korisnik);
        $this->assertSame(1, Korisnik::count());
    }

    public function test_nepotvrdjen_google_email_se_odbija(): void
    {
        $this->googleVraca('pera@gmail.com', potvrdjen: false);

        $this->get(route('prijava.google.povratak'))
            ->assertRedirect(route('prijava'))
            ->assertSessionHasErrors('prijava');

        $this->assertGuest();
        $this->assertSame(0, Korisnik::count());
    }

    public function test_neispravan_state_vraca_na_prijavu(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $this->get(route('prijava.google.povratak'))
            ->assertRedirect(route('prijava'))
            ->assertSessionHasErrors('prijava');

        $this->assertGuest();
    }

    public function test_odbijena_saglasnost_vraca_na_prijavu(): void
    {
        $this->get(route('prijava.google.povratak', ['error' => 'access_denied']))
            ->assertRedirect(route('prijava'))
            ->assertSessionHasErrors('prijava');
    }

    public function test_google_korisnik_moze_da_postavi_lozinku_i_prijavi_se_klasicno(): void
    {
        Schema::create('tokeni_reset_lozinke', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->char('token_hash', 64)->unique();
            $tabela->dateTime('istice_at');
            $tabela->timestamp('kreirano_at')->useCurrent();
        });
        Mail::fake();

        $this->googleVraca('pera@gmail.com');
        $this->get(route('prijava.google.povratak'));
        $this->post(route('odjava'));

        app(ResetLozinkeService::class)->posaljiLink('pera@gmail.com');
        $link = null;
        Mail::assertSent(ResetLozinkeMail::class, function (ResetLozinkeMail $mail) use (&$link) {
            $link = $mail->link;

            return true;
        });
        $token = basename(parse_url($link, PHP_URL_PATH));
        $this->assertTrue(app(ResetLozinkeService::class)->resetuj($token, 'novaLozinka1'));

        $this->post(route('prijava'), ['email' => 'pera@gmail.com', 'lozinka' => 'novaLozinka1'])
            ->assertRedirect();
        $this->assertAuthenticated();
    }
}
