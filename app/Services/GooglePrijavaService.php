<?php

namespace App\Services;

use App\Models\Korisnik;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser as GoogleKorisnik;

class GooglePrijavaService
{
    /** Koliko puta se ponavlja upis kada drugi zahtev u međuvremenu zauzme isto ime ili email. */
    private const BROJ_POKUSAJA = 3;

    public function __construct(private PredloziKorisnickogImenaService $predlozi) {}

    /**
     * Nalog za Google profil: po Google ID-ju, pa po email-u, a ako ga nema, pravi se nov.
     * Vraća null ako Google nije potvrdio email adresu.
     */
    public function korisnikZa(GoogleKorisnik $google): ?Korisnik
    {
        $email = mb_strtolower(trim((string) $google->getEmail()));

        if ($email === '' || ($google->getRaw()['email_verified'] ?? false) !== true) {
            return null;
        }

        for ($pokusaj = 1; ; $pokusaj++) {
            try {
                return DB::transaction(fn () => $this->povezi((string) $google->getId(), $email));
            } catch (UniqueConstraintViolationException $e) {
                if ($pokusaj >= self::BROJ_POKUSAJA) {
                    throw $e;
                }
            }
        }
    }

    private function povezi(string $googleId, string $email): Korisnik
    {
        $korisnik = Korisnik::where('google_id', $googleId)->first();

        if ($korisnik !== null) {
            return $korisnik;
        }

        $korisnik = Korisnik::where('email', $email)->first();

        if ($korisnik !== null) {
            $korisnik->google_id = $googleId;

            if (! $korisnik->jeVerifikovan()) {
                // Neko je mogao da registruje tuđu adresu i nikad je ne potvrdi:
                // njegova lozinka i "zapamti me" ne smeju da prežive povezivanje.
                $korisnik->lozinka_hash = Str::random(64);
                $korisnik->setRememberToken(Str::random(60));
                $korisnik->email_verifikovan_at = now();
            }

            $korisnik->save();

            return $korisnik;
        }

        // Lozinku korisnik ne zna; ako Google prijava ne radi, postavlja je preko "Zaboravljena lozinka?".
        $korisnik = new Korisnik([
            'korisnicko_ime' => $this->korisnickoIme($email),
            'email' => $email,
            'lozinka_hash' => Str::random(64),
            'google_id' => $googleId,
        ]);
        $korisnik->email_verifikovan_at = now();
        $korisnik->save();

        return $korisnik;
    }

    /**
     * Slobodno korisničko ime iz dela email-a pre "@", bez pitanja korisnika.
     */
    private function korisnickoIme(string $email): string
    {
        $ime = trim(preg_replace('/[^\pL\pM\pN_-]+/u', '_', Str::before($email, '@')), '_-');
        $ime = mb_substr($ime !== '' ? $ime : 'korisnik', 0, PredloziKorisnickogImenaService::MAKS_DUZINA);

        if (! Korisnik::where('korisnicko_ime', $ime)->exists()) {
            return $ime;
        }

        return $this->predlozi->predlozi($ime, 1)[0]
            ?? mb_substr($ime, 0, PredloziKorisnickogImenaService::MAKS_DUZINA - 7).'_'.Str::lower(Str::random(6));
    }
}
