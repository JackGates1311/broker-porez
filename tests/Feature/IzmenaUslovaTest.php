<?php

namespace Tests\Feature;

use App\Mail\IzmenaUslovaMail;
use App\Models\Korisnik;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\DomenskeTabele;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class IzmenaUslovaTest extends TestCase
{
    use DomenskeTabele;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->napraviDomenskeTabele();
        $this->withoutVite();
        Mail::fake();
        Route::middleware(['web', 'auth', 'verifikovan', 'obaveznik'])->get('/proba-uslovi', fn () => 'propusteno');
    }

    private function novaVerzija(string $vaziOd = '2026-11-05'): void
    {
        config([
            'pravno.verzija' => '2026-11-05',
            'pravno.vazi_od' => $vaziOd,
            'pravno.izmene' => ['Dodat je novi obrađivač za slanje mejlova.'],
        ]);
    }

    public function test_bez_opisa_izmena_ne_salje_nista(): void
    {
        $this->spremanKorisnik();

        $this->artisan('pravno:obavesti')->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_proba_samo_broji(): void
    {
        $this->spremanKorisnik();
        $this->novaVerzija();

        $this->artisan('pravno:obavesti', ['--proba' => true])
            ->expectsOutputToContain(': 1 korisnika. Ništa nije poslato.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_obavestava_svakog_verifikovanog_korisnika_jednom(): void
    {
        $stari = $this->spremanKorisnik('pera');
        $bezUslova = $this->spremanKorisnik('mika');
        $bezUslova->forceFill(['uslovi_verzija' => null])->save();
        $neverifikovan = Korisnik::create(['korisnicko_ime' => 'zika', 'email' => 'zika@example.com', 'lozinka_hash' => 'Lozinka123']);

        $this->novaVerzija();
        $vecPrihvatio = $this->spremanKorisnik('laza');

        $this->artisan('pravno:obavesti')->expectsOutputToContain('Poslato: 2, neuspešno: 0, preostalo: 0.')->assertSuccessful();

        Mail::assertSent(IzmenaUslovaMail::class, 2);
        Mail::assertSent(IzmenaUslovaMail::class, fn (IzmenaUslovaMail $mail) => $mail->hasTo('pera@example.com')
            && $mail->izmene === ['Dodat je novi obrađivač za slanje mejlova.']);
        Mail::assertSent(IzmenaUslovaMail::class, fn (IzmenaUslovaMail $mail) => $mail->hasTo('mika@example.com'));
        Mail::assertNotSent(IzmenaUslovaMail::class, fn (IzmenaUslovaMail $mail) => $mail->hasTo($neverifikovan->email) || $mail->hasTo($vecPrihvatio->email));
        $this->assertSame('2026-11-05', $stari->fresh()->uslovi_obavesten_verzija);

        // Ponovno pokretanje ne šalje isti mejl.
        $this->artisan('pravno:obavesti')->expectsOutputToContain('Poslato: 0')->assertSuccessful();
        Mail::assertSent(IzmenaUslovaMail::class, 2);
    }

    public function test_limit_deli_slanje_na_vise_pokretanja(): void
    {
        $this->spremanKorisnik('pera');
        $this->spremanKorisnik('mika');
        $this->novaVerzija();

        $this->artisan('pravno:obavesti', ['--limit' => 1])->expectsOutputToContain('preostalo: 1.')->assertSuccessful();
        $this->artisan('pravno:obavesti', ['--limit' => 1])->expectsOutputToContain('preostalo: 0.')->assertSuccessful();

        Mail::assertSent(IzmenaUslovaMail::class, 2);
    }

    public function test_mejl_navodi_datum_izmene_i_linkove(): void
    {
        $this->novaVerzija();

        $html = (new IzmenaUslovaMail($this->spremanKorisnik(), config('pravno.izmene')))->render();

        $this->assertStringContainsString('05.11.2026.', $html);
        $this->assertStringContainsString('Dodat je novi obrađivač za slanje mejlova.', $html);
        $this->assertStringContainsString(route('pravno.uslovi'), $html);
        $this->assertStringContainsString(route('nalog'), $html);
    }

    public function test_ranija_verzija_vazi_do_stupanja_nove_na_snagu(): void
    {
        $korisnik = $this->spremanKorisnik();
        $this->novaVerzija('2026-11-05');

        // Nova verzija stupa na snagu u ponoć po beogradskom vremenu.
        $this->travelTo(CarbonImmutable::parse('2026-11-04 23:59', 'Europe/Belgrade'), fn () => $this->actingAs($korisnik)->get('/proba-uslovi')->assertOk());
        $this->travelTo(CarbonImmutable::parse('2026-11-05 00:01', 'Europe/Belgrade'), fn () => $this->actingAs($korisnik)->get('/proba-uslovi')
            ->assertRedirect(route('poreski-obaveznik')));
    }
}
