<?php

namespace Tests\Feature\Concerns;

use App\Models\Korisnik;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Test nad pravom MariaDB šemom iz sql/ fajlova (Tests\Baza\PripremaBaze, jednom po pokretanju).
 * Svaki test radi u transakciji koja se na kraju poništi, pa testovi ne vide podatke jedni drugih.
 */
trait TestBaza
{
    use DatabaseTransactions;

    /**
     * Verifikovan korisnik sa popunjenim podacima poreskog obaveznika i prihvaćenim uslovima (prolazi verifikovan i obaveznik).
     */
    protected function spremanKorisnik(string $ime = 'pera'): Korisnik
    {
        $korisnik = Korisnik::create([
            'korisnicko_ime' => $ime,
            'email' => "{$ime}@example.com",
            'lozinka_hash' => 'Lozinka123',
        ]);
        $korisnik->forceFill(['email_verifikovan_at' => now()])->save();
        $korisnik->prihvatiUslove();

        $korisnik->poreskiObaveznik()->create([
            'tip_obaveznika' => 1,
            'pib' => '0101990710001',
            'ime_prezime' => 'Petar Petrović',
            'prebivaliste' => 'Novi Sad',
            'opstina_sifra' => '223',
            'adresa' => 'Bulevar oslobođenja 1',
            'telefon' => '0601234567',
            'email' => "{$ime}@example.com",
            'jmbg_podnosioca' => '0101990710001',
            'zemlja_rezidentstva' => 'RS',
            'tekuci_racun' => '265000000036005146',
        ]);

        return $korisnik;
    }
}
