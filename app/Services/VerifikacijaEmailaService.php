<?php

namespace App\Services;

use App\Mail\VerifikacioniKodMail;
use App\Models\Korisnik;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class VerifikacijaEmailaService
{
    public const TRAJANJE_KODA_MINUTA = 15;

    public const MAKS_POKUSAJA = 5;

    /**
     * Poništava prethodne kodove, generiše novi i šalje ga korisniku na email.
     */
    public function posaljiKod(Korisnik $korisnik): void
    {
        $korisnik->verifikacioniKodovi()->delete();

        $kod = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $korisnik->verifikacioniKodovi()->create([
            'kod_hash' => Hash::make($kod),
            'istice_at' => now()->addMinutes(self::TRAJANJE_KODA_MINUTA),
        ]);

        Mail::to($korisnik->email)->send(new VerifikacioniKodMail($korisnik, $kod));
    }

    public function imaAktivanKod(Korisnik $korisnik): bool
    {
        return $korisnik->verifikacioniKodovi()
            ->where('istice_at', '>', now())
            ->where('broj_pokusaja', '<', self::MAKS_POKUSAJA)
            ->exists();
    }

    /**
     * Proverava kod i, ako je ispravan, označava email korisnika kao verifikovan.
     */
    public function proveriKod(Korisnik $korisnik, string $kod): bool
    {
        $verifikacioniKod = $korisnik->verifikacioniKodovi()->latest('id')->first();

        if ($verifikacioniKod === null
            || $verifikacioniKod->jeIstekao()
            || $verifikacioniKod->broj_pokusaja >= self::MAKS_POKUSAJA) {
            return false;
        }

        $verifikacioniKod->increment('broj_pokusaja');

        if (! Hash::check($kod, $verifikacioniKod->kod_hash)) {
            return false;
        }

        $korisnik->forceFill(['email_verifikovan_at' => now()])->save();
        $korisnik->verifikacioniKodovi()->delete();

        return true;
    }
}
