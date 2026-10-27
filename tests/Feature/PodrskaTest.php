<?php

namespace Tests\Feature;

use App\Mail\PodrskaMail;
use App\Models\Korisnik;
use App\Models\PorukaPodrske;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\TestBaza;
use Tests\TestCase;

class PodrskaTest extends TestCase
{
    use TestBaza;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        config(['podrska.email' => 'podrska@brokerporez.net']);
    }

    private function insajder(string $ime = 'pera'): Korisnik
    {
        $korisnik = $this->spremanKorisnik($ime);
        $korisnik->forceFill(['is_insajder' => true])->save();

        return $korisnik;
    }

    public function test_korisnik_sam_ukljucuje_i_iskljucuje_widget(): void
    {
        $korisnik = $this->spremanKorisnik();

        $this->actingAs($korisnik)->get(route('nalog'))
            ->assertOk()
            ->assertSee('Uključi dugme')
            ->assertDontSee('podrskaModal');

        $this->actingAs($korisnik)
            ->put(route('nalog.insajder'), ['insajder' => '1'])
            ->assertRedirect(route('nalog'))
            ->assertSessionHas('status');
        $this->assertTrue($korisnik->fresh()->jeInsajder());

        $this->actingAs($korisnik->fresh())->get(route('nalog'))
            ->assertSee('Isključi dugme')
            ->assertSee('id="podrskaModal"', escape: false);

        $this->actingAs($korisnik)->put(route('nalog.insajder'), ['insajder' => '0']);
        $this->assertFalse($korisnik->fresh()->jeInsajder());

        $this->actingAs($korisnik)
            ->put(route('nalog.insajder'), ['insajder' => 'mozda'])
            ->assertSessionHasErrors('insajder');
    }

    public function test_najvise_pet_poruka_za_deset_minuta_i_deset_dnevno(): void
    {
        Mail::fake();
        $korisnik = $this->insajder();
        $posalji = fn () => $this->actingAs($korisnik)->postJson(route('podrska.posalji'), ['poruka' => 'Utisak']);

        for ($i = 0; $i < 5; $i++) {
            $posalji()->assertCreated();
        }
        $posalji()->assertTooManyRequests();

        $this->travel(11)->minutes();
        for ($i = 0; $i < 5; $i++) {
            $posalji()->assertCreated();
        }

        $this->travel(11)->minutes();
        $posalji()->assertTooManyRequests();

        $this->assertSame(10, PorukaPodrske::count());
    }

    public function test_widget_vidi_samo_insajder(): void
    {
        $this->actingAs($this->spremanKorisnik('mika'))->get(route('nalog'))
            ->assertOk()
            ->assertDontSee('podrskaModal');

        $this->actingAs($this->insajder())->get(route('nalog'))
            ->assertOk()
            ->assertSee('id="podrskaModal"', escape: false)
            ->assertSee(route('podrska.posalji'), escape: false);
    }

    public function test_ruta_ne_postoji_za_korisnika_koji_nije_insajder(): void
    {
        Mail::fake();

        $this->actingAs($this->spremanKorisnik())
            ->postJson(route('podrska.posalji'), ['poruka' => 'Zdravo'])
            ->assertNotFound();

        $this->assertSame(0, PorukaPodrske::count());
        Mail::assertNothingSent();
    }

    public function test_neverifikovan_insajder_ide_na_verifikaciju(): void
    {
        $korisnik = $this->insajder();
        $korisnik->forceFill(['email_verifikovan_at' => null])->save();

        $this->actingAs($korisnik)
            ->post(route('podrska.posalji'), ['poruka' => 'Zdravo'])
            ->assertRedirect(route('verifikacija'));
    }

    public function test_neispravna_poruka_i_prilozi_se_odbijaju(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)
            ->postJson(route('podrska.posalji'), ['poruka' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('poruka');

        $this->actingAs($korisnik)
            ->postJson(route('podrska.posalji'), [
                'poruka' => 'Prilog',
                'prilozi' => [UploadedFile::fake()->create('alat.exe', 10, 'application/x-msdownload')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('prilozi.0');

        $this->actingAs($korisnik)
            ->postJson(route('podrska.posalji'), [
                'poruka' => 'Prevelik',
                'prilozi' => [UploadedFile::fake()->create('izvod.pdf', 6000, 'application/pdf')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('prilozi.0');

        $this->actingAs($korisnik)
            ->postJson(route('podrska.posalji'), [
                'poruka' => 'Previše ukupno',
                'prilozi' => array_map(fn (int $i) => UploadedFile::fake()->create("deo{$i}.pdf", 4000, 'application/pdf'), [1, 2, 3]),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('prilozi');

        $this->actingAs($korisnik)
            ->postJson(route('podrska.posalji'), [
                'poruka' => 'Previše fajlova',
                'prilozi' => array_map(fn (int $i) => UploadedFile::fake()->create("b{$i}.txt", 1, 'text/plain'), range(1, 6)),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('prilozi');

        $this->assertSame(0, PorukaPodrske::count());
        Mail::assertNothingSent();
    }

    public function test_poruka_se_cuva_i_salje_sa_reply_to_korisnika(): void
    {
        Mail::fake();
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)
            ->withHeader('User-Agent', 'Testni pregledač')
            ->postJson(route('podrska.posalji'), [
                'naslov' => "Greška na uvozu\r\nBcc: neko@example.com",
                'poruka' => "Uvoz pada.\nDrugi red.",
                'stranica' => 'http://localhost/uvoz?q=AAPL',
                'prilozi' => [
                    UploadedFile::fake()->create('../snimak ekrana.png', 50, 'image/png'),
                    UploadedFile::fake()->create('izvod.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('poruka', 'Hvala! Poruka je primljena, odgovor stiže na email adresu vašeg naloga.');

        $poruka = PorukaPodrske::with('prilozi')->sole();
        $this->assertSame($korisnik->id, $poruka->korisnik_id);
        $this->assertSame('Greška na uvozu Bcc: neko@example.com', $poruka->naslov);
        $this->assertSame("Uvoz pada.\nDrugi red.", $poruka->poruka);
        $this->assertSame('http://localhost/uvoz?q=AAPL', $poruka->stranica);
        $this->assertSame('Testni pregledač', $poruka->user_agent);
        $this->assertNotNull($poruka->poslato_at);

        $this->assertSame(['snimak ekrana.png', 'izvod.pdf'], $poruka->prilozi->pluck('naziv')->all());

        foreach ($poruka->prilozi as $prilog) {
            $this->assertStringStartsWith("podrska/{$poruka->id}/", $prilog->putanja);
            $this->assertSame($korisnik->id, $prilog->korisnik_id);
            Storage::disk('local')->assertExists($prilog->putanja);
        }

        Mail::assertSent(PodrskaMail::class, function (PodrskaMail $mail) use ($poruka, $korisnik) {
            $prilog = $poruka->prilozi->first();

            return $mail->hasTo('podrska@brokerporez.net')
                && $mail->hasReplyTo($korisnik->email, $korisnik->korisnicko_ime)
                && $mail->hasSubject("[Insajder #{$poruka->id}] Greška na uvozu Bcc: neko@example.com")
                && count($mail->attachments()) === 2
                && $mail->hasAttachment(Attachment::fromStorageDisk('local', $prilog->putanja)->as('snimak ekrana.png')->withMime('image/png'));
        });
    }

    public function test_bez_naslova_ide_podrazumevani_subject_i_bez_js_povratak_na_stranicu(): void
    {
        Mail::fake();
        $korisnik = $this->insajder();

        $this->actingAs($korisnik)
            ->from(route('nalog'))
            ->post(route('podrska.posalji'), ['naslov' => '  ', 'poruka' => 'Sve radi.'])
            ->assertRedirect(route('nalog'))
            ->assertSessionHas('status');

        $poruka = PorukaPodrske::sole();
        $this->assertNull($poruka->naslov);
        Mail::assertSent(PodrskaMail::class, fn (PodrskaMail $mail) => $mail->hasSubject("[Insajder #{$poruka->id}] Povratna informacija"));
    }

    public function test_poruka_ostaje_kada_mejl_ne_ode_i_komanda_je_salje_ponovo(): void
    {
        $korisnik = $this->insajder();
        // SMTP server koji ne odgovara: slanje baca izuzetak.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->actingAs($korisnik)
            ->postJson(route('podrska.posalji'), ['poruka' => 'Javljam grešku.'])
            ->assertCreated();

        $this->assertNull(PorukaPodrske::sole()->poslato_at);

        Mail::fake();
        $this->artisan('podrska:posalji-neposlate')
            ->expectsOutput('Poslato: 1, neuspešno: 0.')
            ->assertSuccessful();

        $this->assertNotNull(PorukaPodrske::sole()->poslato_at);
        Mail::assertSent(PodrskaMail::class, 1);
    }
}
