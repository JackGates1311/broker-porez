<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GooglePrijavaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GooglePrijavaController extends Controller
{
    public function preusmeri(): SymfonyRedirectResponse
    {
        // Bez ovoga Google odmah vraća poslednji korišćeni nalog, pa korisnik sa više naloga ne može da bira.
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function povratak(Request $request, GooglePrijavaService $google): RedirectResponse
    {
        // Korisnik je odustao, ili nalog nije među test korisnicima dok je aplikacija u test fazi.
        if ($request->filled('error')) {
            return $this->neuspeh();
        }

        try {
            /** @var AbstractUser $profil */
            $profil = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->neuspeh();
        }

        $korisnik = $google->korisnikZa($profil);

        if ($korisnik === null) {
            return $this->neuspeh('Google nalog nema potvrđenu email adresu. Prijavite se putem mejla i lozinke.');
        }

        Auth::login($korisnik);
        $request->session()->regenerate();

        return redirect()->intended(route('pocetna', absolute: false));
    }

    private function neuspeh(string $poruka = 'Prijava preko Google naloga nije uspela. Pokušajte ponovo ili se prijavite putem mejla i lozinke.'): RedirectResponse
    {
        return redirect()->route('prijava')->withErrors(['prijava' => $poruka]);
    }
}
