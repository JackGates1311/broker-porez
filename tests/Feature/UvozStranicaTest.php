<?php

namespace Tests\Feature;

use App\Models\Korisnik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\DomenskeTabele;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class UvozStranicaTest extends TestCase
{
    use DomenskeTabele;
    use RefreshDatabase;

    private Korisnik $korisnik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->napraviDomenskeTabele();
        $this->withoutVite();
        $this->korisnik = $this->spremanKorisnik();
    }

    public function test_podrazumevano_je_trading_212_a_ibkr_je_onemogucen(): void
    {
        $odgovor = $this->actingAs($this->korisnik)->get(route('uvoz'))
            ->assertOk()
            ->assertSee('<select id="izvor" name="izvor"', false)
            ->assertSee('Uvezi transakcije')
            ->assertDontSee('Novi šablon');

        $this->assertMatchesRegularExpression('/<option value="trading212"\s+selected\s*>/', $odgovor->getContent());
        $this->assertMatchesRegularExpression('/<option value="ibkr"\s+disabled\s*>\s*Interactive Brokers \(uskoro\)/', $odgovor->getContent());
    }

    public function test_drugi_broker_prikazuje_carobnjak_i_sablone(): void
    {
        $this->korisnik->sabloniUvoza()->create(['naziv' => 'Moj broker', 'podesavanja' => []]);

        $this->actingAs($this->korisnik)->get(route('uvoz', ['izvor' => 'rucni']))
            ->assertOk()
            ->assertSee('Novi šablon')
            ->assertSee('Moj broker')
            ->assertSee(route('uvoz.rucni'), false);
    }

    public function test_nedostupan_izvor_pada_na_trading_212(): void
    {
        $html = $this->actingAs($this->korisnik)->get(route('uvoz', ['izvor' => 'ibkr']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="trading212"\s+selected\s*>/', $html);
        $this->assertStringNotContainsString('Novi šablon', $html);
    }

    public function test_dugmad_za_kurseve_su_primarna(): void
    {
        $html = $this->actingAs($this->korisnik)->get(route('uvoz'))->getContent();

        $this->assertMatchesRegularExpression('/class="btn btn-primary btn-sm"[^>]*>Preuzmi kurseve koji nedostaju/', $html);
        $this->assertMatchesRegularExpression('/class="btn btn-primary">Uvezi kurseve/', $html);
    }
}
