<?php

namespace Tests\Feature;

use App\Mail\ZadovoljstvoIzvestajMail;
use App\Models\Korisnik;
use App\Models\OcenaZadovoljstva;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\TestBaza;
use Tests\TestCase;

class ZadovoljstvoTest extends TestCase
{
    use TestBaza;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['podrska.email' => 'podrska@brokerporez.net']);
    }

    private function insajder(string $ime = 'pera'): Korisnik
    {
        $korisnik = $this->spremanKorisnik($ime);
        $korisnik->forceFill(['is_insajder' => true, 'zadovoljstvo_od' => now()->subMinute()])->save();

        return $korisnik->fresh();
    }

    public function test_kartica_je_samo_za_insajdere_kojima_je_vreme_za_pitanje(): void
    {
        $obican = $this->spremanKorisnik('mika');
        $this->actingAs($obican->fresh())->get(route('poreski-obaveznik'))
            ->assertOk()
            ->assertDontSee('data-zadovoljstvo', escape: false);

        $insajder = $this->insajder();
        $this->actingAs($insajder)->get(route('poreski-obaveznik'))
            ->assertSee('data-zadovoljstvo', escape: false)
            ->assertSee(route('zadovoljstvo.sacuvaj'), escape: false);

        $insajder->forceFill(['zadovoljstvo_od' => now()->addDay()])->save();
        $this->actingAs($insajder->fresh())->get(route('poreski-obaveznik'))
            ->assertDontSee('data-zadovoljstvo', escape: false);

        $insajder->forceFill(['zadovoljstvo_od' => null])->save();
        $this->actingAs($insajder->fresh())->get(route('poreski-obaveznik'))
            ->assertDontSee('data-zadovoljstvo', escape: false);
    }

    public function test_novi_korisnik_dobija_vreme_iz_aplikacije_a_ne_iz_baze(): void
    {
        // DEFAULT CURRENT_TIMESTAMP je u zoni MariaDB sesije (lokalno CET), a aplikacija čita UTC: sat unapred.
        $this->freezeSecond();
        $korisnik = Korisnik::create(['korisnicko_ime' => 'novi', 'email' => 'novi@example.com', 'lozinka_hash' => 'Lozinka123']);

        $this->assertNotNull($korisnik->zadovoljstvo_od);
        $this->assertTrue($korisnik->zadovoljstvo_od->eq(now()));
        $this->assertTrue($korisnik->fresh()->zadovoljstvo_od->eq(now()));
    }

    public function test_obican_korisnik_ne_moze_da_salje_ocenu(): void
    {
        $this->actingAs($this->spremanKorisnik())
            ->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'posalji', 'ocena' => 5, 'povod' => 'ppdg-3r-pdf'])
            ->assertNotFound();

        $this->assertSame(0, OcenaZadovoljstva::count());
    }

    public function test_ocena_se_cuva_i_sledece_pitanje_je_za_90_dana(): void
    {
        $this->freezeSecond();
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)
            ->postJson(route('zadovoljstvo.sacuvaj'), [
                'akcija' => 'posalji', 'ocena' => 4, 'komentar' => 'XML je prošao.', 'povod' => 'ppdg-3r-xml',
            ])
            ->assertCreated()
            ->assertJsonPath('poruka', 'Hvala na oceni!');

        $ocena = OcenaZadovoljstva::sole();
        $this->assertSame([$korisnik->id, 4, 'XML je prošao.', 'ppdg-3r-xml'], [$ocena->korisnik_id, $ocena->ocena, $ocena->komentar, $ocena->povod]);
        $this->assertTrue($korisnik->fresh()->zadovoljstvo_od->eq(now()->addDays(90)));
        $this->assertFalse($korisnik->fresh()->trebaPitatiZadovoljstvo());

        $this->travel(91)->days();
        $this->assertTrue($korisnik->fresh()->trebaPitatiZadovoljstvo());
    }

    public function test_ne_sada_odlaze_7_dana_a_ne_pitaj_vise_zauvek_bez_ocene(): void
    {
        $this->freezeSecond();
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'kasnije'])->assertOk();
        $this->assertTrue($korisnik->fresh()->zadovoljstvo_od->eq(now()->addDays(7)));

        $this->actingAs($korisnik)->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'nikad'])->assertOk();
        $this->assertNull($korisnik->fresh()->zadovoljstvo_od);

        $this->assertSame(0, OcenaZadovoljstva::count());
    }

    public function test_neispravna_ocena_ili_povod_se_odbijaju(): void
    {
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)
            ->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'posalji', 'povod' => 'ppdg-3r-pdf'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.ocena.0', 'Izaberite ocenu od 1 do 5.');

        $this->actingAs($korisnik)
            ->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'posalji', 'ocena' => 6, 'povod' => 'ppdg-3r-pdf'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ocena');

        $this->actingAs($korisnik)
            ->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'posalji', 'ocena' => 3, 'povod' => 'nesto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('povod');

        $this->actingAs($korisnik)
            ->postJson(route('zadovoljstvo.sacuvaj'), ['akcija' => 'obrisi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('akcija');

        $this->assertSame(0, OcenaZadovoljstva::count());
    }

    public function test_iskljucene_povratne_informacije_iskljucuju_i_pitanje(): void
    {
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)->put(route('nalog.insajder'), ['insajder' => '0']);

        $this->assertFalse($korisnik->fresh()->trebaPitatiZadovoljstvo());
        $this->actingAs($korisnik->fresh())->get(route('poreski-obaveznik'))
            ->assertDontSee('data-zadovoljstvo', escape: false);
    }

    public function test_mesecni_izvestaj_se_ispisuje_i_salje_mejlom(): void
    {
        Mail::fake();
        $pera = $this->insajder();
        $mika = $this->insajder('mika');

        $this->travelTo('2026-09-15 10:00:00');
        $pera->oceneZadovoljstva()->create(['ocena' => 2, 'povod' => 'pp-opo-pdf']);

        $this->travelTo('2026-10-03 10:00:00');
        $pera->oceneZadovoljstva()->create(['ocena' => 5, 'povod' => 'ppdg-3r-xml', 'komentar' => 'Odlično.']);
        $mika->oceneZadovoljstva()->create(['ocena' => 3, 'povod' => 'pp-opo-pdf', 'komentar' => 'Nisam našao PP OPO.']);

        $this->travelTo('2026-11-01 08:00:00');

        $this->artisan('zadovoljstvo:izvestaj')
            ->expectsOutputToContain('Zadovoljstvo insajdera za oktobar 2026. godine')
            ->expectsOutputToContain('[3] mika, 03.10.2026.: Nisam našao PP OPO.')
            ->assertSuccessful();
        Mail::assertNothingSent();

        $this->artisan('zadovoljstvo:izvestaj', ['--mejl' => true])
            ->expectsOutput('Izveštaj je poslat na podrska@brokerporez.net.')
            ->assertSuccessful();

        Mail::assertSent(ZadovoljstvoIzvestajMail::class, function (ZadovoljstvoIzvestajMail $mail) {
            $mail->assertSeeInHtml('Nisam našao PP OPO.');
            $mail->assertSeeInHtml('Zadovoljstvo insajdera za oktobar 2026. godine');

            return $mail->hasTo('podrska@brokerporez.net')
                && $mail->hasSubject('Zadovoljstvo insajdera za oktobar 2026. godine (ocena 4,00, 2 odgovora)')
                && $mail->izvestaj['prethodni']['broj'] === 1;
        });
    }

    public function test_prazan_mesec_ne_salje_mejl_a_neispravan_period_je_greska(): void
    {
        Mail::fake();

        $this->artisan('zadovoljstvo:izvestaj', ['--period' => '2026-01', '--mejl' => true])
            ->expectsOutput('Nema odgovora u ovom mesecu, mejl nije poslat.')
            ->assertSuccessful();
        Mail::assertNothingSent();

        $this->artisan('zadovoljstvo:izvestaj', ['--period' => 'juce'])->assertFailed();
    }
}
