<?php

namespace App\Services;

use App\Mail\ResetLozinkeMail;
use App\Models\Korisnik;
use App\Models\TokenResetLozinke;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResetLozinkeService
{
    public const TRAJANJE_LINKA_MINUTA = VerifikacijaEmailaService::TRAJANJE_KODA_MINUTA;

    /**
     * Šalje link za resetovanje ako nalog postoji. Za nepostojeći email ne radi ništa,
     * da se ne bi otkrilo koji su emailovi registrovani.
     */
    public function posaljiLink(string $email): void
    {
        $korisnik = Korisnik::where('email', mb_strtolower(trim($email)))->first();

        if ($korisnik === null) {
            return;
        }

        $korisnik->tokeniResetLozinke()->delete();

        $token = Str::random(64);

        $korisnik->tokeniResetLozinke()->create([
            'token_hash' => $this->hes($token),
            'istice_at' => now()->addMinutes(self::TRAJANJE_LINKA_MINUTA),
        ]);

        Mail::to($korisnik->email)->send(new ResetLozinkeMail($korisnik, route('lozinka.reset', $token)));
    }

    /**
     * Vraća korisnika kome pripada važeći token, ili null ako token ne postoji ili je istekao.
     */
    public function nadjiKorisnika(string $token): ?Korisnik
    {
        $zapis = TokenResetLozinke::where('token_hash', $this->hes($token))->first();

        if ($zapis === null || $zapis->jeIstekao()) {
            return null;
        }

        return $zapis->korisnik;
    }

    /**
     * Postavlja novu lozinku i poništava sve tokene korisnika.
     */
    public function resetuj(string $token, string $lozinka): bool
    {
        $korisnik = $this->nadjiKorisnika($token);

        if ($korisnik === null) {
            return false;
        }

        DB::transaction(function () use ($korisnik, $lozinka) {
            // Klik na link iz mejla dokazuje vlasništvo nad email adresom.
            $korisnik->email_verifikovan_at ??= now();
            $korisnik->lozinka_hash = $lozinka;
            $korisnik->setRememberToken(Str::random(60));
            $korisnik->save();

            $korisnik->tokeniResetLozinke()->delete();
        });

        return true;
    }

    private function hes(string $token): string
    {
        return hash('sha256', $token);
    }
}
