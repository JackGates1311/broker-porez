<?php

namespace Tests\Feature\Concerns;

use App\Models\Korisnik;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domenske tabele su u sql/, a ne u migracijama, pa ih testovi prave sami
 * (SQLite verzija sql/*.sql zaključno sa sql/uvoz/izvori_uvoza.sql, sql/porezi/tekuci_racun_pasos.sql, sql/auth/google_oauth.sql
 * sql/pravno/privatnost.sql i sql/pravno/obavestenje_uslova.sql).
 */
trait DomenskeTabele
{
    protected function napraviDomenskeTabele(): void
    {
        Schema::create('korisnici', function (Blueprint $tabela) {
            $tabela->increments('id');
            $tabela->string('korisnicko_ime', 50)->unique();
            $tabela->string('email', 100)->unique();
            $tabela->string('lozinka_hash');
            $tabela->timestamp('email_verifikovan_at')->nullable();
            $tabela->string('remember_token', 100)->nullable();
            $tabela->string('google_id')->nullable()->unique();
            $tabela->timestamp('kreirano_at')->useCurrent();
            $tabela->timestamp('uslovi_prihvaceni_at')->nullable();
            $tabela->string('uslovi_verzija', 20)->nullable();
            $tabela->string('uslovi_obavesten_verzija', 20)->nullable();
        });

        Schema::create('obrisani_nalozi', function (Blueprint $tabela) {
            $tabela->increments('id');
            $tabela->char('registrovan_mesec', 7);
            $tabela->string('nacin_prijave', 10);
            $tabela->timestamp('obrisano_at')->useCurrent();
        });

        Schema::create('poreski_obaveznik', function (Blueprint $tabela) {
            $tabela->unsignedInteger('korisnik_id')->primary();
            $tabela->tinyInteger('tip_obaveznika')->nullable();
            $tabela->text('pib')->nullable();
            $tabela->string('ime_prezime', 150)->nullable();
            $tabela->string('adresa')->nullable();
            $tabela->string('prebivaliste')->nullable();
            $tabela->char('opstina_sifra', 3)->nullable();
            $tabela->string('telefon', 30)->nullable();
            $tabela->string('email', 100)->nullable();
            $tabela->text('jmbg_podnosioca')->nullable();
            $tabela->string('zemlja_rezidentstva', 3)->nullable();
            $tabela->string('pib_punomocnika', 13)->nullable();
            $tabela->text('tekuci_racun')->nullable();
            $tabela->text('broj_pasosa')->nullable();
            $tabela->timestamp('azurirano_at')->nullable();
        });

        Schema::create('imovina', function (Blueprint $tabela) {
            $tabela->increments('id');
            $tabela->string('isin', 12)->unique();
            $tabela->string('simbol', 30);
            $tabela->string('naziv', 150)->nullable();
            $tabela->timestamp('kreirano_at')->useCurrent();
        });

        Schema::create('kursevi', function (Blueprint $tabela) {
            $tabela->increments('id');
            $tabela->date('datum');
            $tabela->string('valuta', 5);
            $tabela->decimal('srednji_kurs', 20, 10);
            $tabela->unique(['datum', 'valuta']);
        });

        Schema::create('transakcije', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->string('broker_transakcija_id', 100)->nullable();
            $tabela->unsignedInteger('imovina_id')->nullable();
            $tabela->string('tip_akcije', 50);
            $tabela->string('tip', 20);
            $tabela->string('izvor', 20)->default('TRADING212');
            $tabela->dateTime('vreme_utc');
            $tabela->decimal('kolicina', 28, 10)->default(0);
            $tabela->decimal('cena_po_akciji', 28, 10)->default(0);
            $tabela->string('valuta_cene', 5)->nullable();
            $tabela->decimal('kurs', 20, 10)->nullable();
            $tabela->decimal('kurs_porez', 20, 10)->nullable();
            $tabela->decimal('ukupno', 28, 10);
            $tabela->string('valuta_ukupno', 5);
            $tabela->decimal('porez_po_odbitku', 28, 10)->default(0);
            $tabela->string('valuta_poreza', 5)->nullable();
            $tabela->decimal('provizija', 28, 10)->default(0);
            $tabela->string('valuta_provizije', 5)->nullable();
            $tabela->text('napomena')->nullable();
            $tabela->char('jedinstveni_kljuc', 64);
            $tabela->timestamp('kreirano_at')->useCurrent();
            $tabela->unique(['korisnik_id', 'jedinstveni_kljuc']);
        });

        Schema::create('poreski_lotovi', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->unsignedBigInteger('transakcija_kupovine_id');
            $tabela->unsignedInteger('imovina_id');
            $tabela->decimal('pocetna_kolicina', 28, 10);
            $tabela->decimal('preostala_kolicina', 28, 10);
            $tabela->decimal('nabavna_cena_po_jedinici', 28, 10);
            $tabela->timestamp('kreirano_at')->useCurrent();
        });

        Schema::create('alokacije_lotova_prodaje', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedInteger('korisnik_id');
            $tabela->unsignedBigInteger('transakcija_prodaje_id');
            $tabela->unsignedBigInteger('poreski_lot_id');
            $tabela->decimal('iskoriscena_kolicina', 28, 10);
            $tabela->decimal('nabavna_vrednost_rsd', 28, 10);
            $tabela->timestamp('kreirano_at')->useCurrent();
        });

        Schema::create('sabloni_uvoza', function (Blueprint $tabela) {
            $tabela->increments('id');
            $tabela->unsignedInteger('korisnik_id');
            $tabela->string('naziv', 100);
            $tabela->json('podesavanja');
            $tabela->timestamp('kreirano_at')->useCurrent();
            $tabela->unique(['korisnik_id', 'naziv']);
        });
    }

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
