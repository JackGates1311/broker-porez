<?php

namespace Tests\Feature;

use App\Mail\ResetLozinkeMail;
use App\Models\Korisnik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ResetLozinkeTest extends TestCase
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

        Schema::create('tokeni_reset_lozinke', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->char('token_hash', 64)->unique();
            $tabela->dateTime('istice_at');
            $tabela->timestamp('kreirano_at')->useCurrent();
            $tabela->foreign('korisnik_id')->references('id')->on('korisnici')->cascadeOnDelete();
        });

        $this->withoutVite();
        Mail::fake();

        $this->korisnik = Korisnik::create([
            'korisnicko_ime' => 'pera',
            'email' => 'pera@example.com',
            'lozinka_hash' => 'staraLozinka1',
        ]);
    }

    public function test_prijava_prikazuje_link_za_zaboravljenu_lozinku(): void
    {
        $this->get(route('prijava'))
            ->assertOk()
            ->assertSee(route('lozinka.zaboravljena'), escape: false)
            ->assertSee('Zaboravljena lozinka?');
    }

    public function test_postojeci_email_dobija_link(): void
    {
        $this->post(route('lozinka.zaboravljena'), ['email' => ' Pera@Example.com '])
            ->assertRedirect(route('lozinka.poslato'));

        Mail::assertSent(ResetLozinkeMail::class, fn (ResetLozinkeMail $mail) => $mail->hasTo('pera@example.com'));
        $this->assertSame(1, $this->korisnik->tokeniResetLozinke()->count());

        $this->get(route('lozinka.poslato'))->assertOk()->assertSee('Proverite email');
    }

    public function test_nepostojeci_email_dobija_isti_odgovor_bez_mejla(): void
    {
        $this->post(route('lozinka.zaboravljena'), ['email' => 'niko@example.com'])
            ->assertRedirect(route('lozinka.poslato'));

        Mail::assertNothingSent();
    }

    public function test_poslato_bez_emaila_vraca_na_formu(): void
    {
        $this->get(route('lozinka.poslato'))->assertRedirect(route('lozinka.zaboravljena'));
    }

    public function test_ispravan_link_prikazuje_formu(): void
    {
        $this->get($this->posaljiLink())
            ->assertOk()
            ->assertSee('name="lozinka_confirmation"', escape: false);
    }

    public function test_istekao_ili_pogresan_link_prikazuje_gresku(): void
    {
        $link = $this->posaljiLink();

        $this->get(route('lozinka.reset', 'pogresan-token'))
            ->assertOk()
            ->assertSee('nije ispravan ili je istekao')
            ->assertDontSee('name="lozinka_confirmation"', escape: false);

        $this->travel(16)->minutes();

        $this->get($link)->assertSee('nije ispravan ili je istekao');
    }

    public function test_nova_lozinka_se_postavlja_i_link_se_ne_moze_ponovo_iskoristiti(): void
    {
        $token = basename($this->posaljiLink());

        $this->post(route('lozinka.reset.sacuvaj'), [
            'token' => $token,
            'lozinka' => 'novaLozinka2',
            'lozinka_confirmation' => 'novaLozinka2',
        ])->assertRedirect(route('prijava'));

        $this->korisnik->refresh();
        $this->assertTrue(Hash::check('novaLozinka2', $this->korisnik->lozinka_hash));
        $this->assertTrue($this->korisnik->jeVerifikovan());
        $this->assertSame(0, $this->korisnik->tokeniResetLozinke()->count());

        $this->post(route('lozinka.reset.sacuvaj'), [
            'token' => $token,
            'lozinka' => 'trecaLozinka3',
            'lozinka_confirmation' => 'trecaLozinka3',
        ])->assertSessionHasErrors('token');

        $this->post(route('prijava'), ['email' => 'pera@example.com', 'lozinka' => 'novaLozinka2'])
            ->assertRedirect(route('pocetna'));
        $this->assertAuthenticatedAs($this->korisnik);
    }

    public function test_potvrda_lozinke_mora_da_se_poklapa(): void
    {
        $token = basename($this->posaljiLink());

        $this->post(route('lozinka.reset.sacuvaj'), [
            'token' => $token,
            'lozinka' => 'novaLozinka2',
            'lozinka_confirmation' => 'drugacija2',
        ])->assertSessionHasErrors('lozinka');

        $this->assertTrue(Hash::check('staraLozinka1', $this->korisnik->refresh()->lozinka_hash));
    }

    private function posaljiLink(): string
    {
        $this->post(route('lozinka.zaboravljena'), ['email' => 'pera@example.com']);

        $link = null;
        Mail::assertSent(ResetLozinkeMail::class, function (ResetLozinkeMail $mail) use (&$link) {
            $link = $mail->link;

            return true;
        });

        return $link;
    }
}
