<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthStraniceTest extends TestCase
{
    public function test_gost_se_sa_pocetne_preusmerava_na_prijavu(): void
    {
        $this->get('/')->assertRedirect('/pocetna');
        $this->get('/pocetna')->assertRedirect(route('prijava'));
    }

    public function test_gost_se_preusmerava_sa_verifikacije_na_prijavu(): void
    {
        $this->get(route('verifikacija'))->assertRedirect(route('prijava'));
    }

    public function test_gost_nema_pristup_poreskoj_knjizi(): void
    {
        foreach (['/kapitalna-dobit', '/dividende', '/uvoz', '/profil', '/izvoz/ppdg-3r?godina=2026&polugodiste=1', '/izvoz/pp-opo/1'] as $adresa) {
            $this->get($adresa)->assertRedirect(route('prijava'));
        }

        $this->post('/uvoz/trading212')->assertRedirect(route('prijava'));
    }

    public function test_stranica_za_prijavu_se_prikazuje(): void
    {
        $this->withoutVite()
            ->get(route('prijava'))
            ->assertOk()
            ->assertSee('Prijavi se')
            ->assertSee('name="lozinka"', escape: false);
    }

    public function test_stranica_za_zaboravljenu_lozinku_se_prikazuje(): void
    {
        $this->withoutVite()
            ->get(route('lozinka.zaboravljena'))
            ->assertOk()
            ->assertSee('Pošalji link')
            ->assertSee('name="email"', escape: false);
    }

    public function test_stranica_za_registraciju_se_prikazuje(): void
    {
        $this->withoutVite()
            ->get(route('registracija'))
            ->assertOk()
            ->assertSee('Registruj se')
            ->assertSee('name="lozinka_confirmation"', escape: false);
    }
}
