<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PrijavaRequest;
use App\Models\Korisnik;
use App\Services\VerifikacijaEmailaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PrijavaController extends Controller
{
    public function create(): View
    {
        return view('auth.prijava');
    }

    public function store(PrijavaRequest $request, VerifikacijaEmailaService $verifikacija): RedirectResponse
    {
        $request->prijavi();
        $request->session()->regenerate();

        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        if (! $korisnik->jeVerifikovan()) {
            if (! $verifikacija->imaAktivanKod($korisnik)) {
                $verifikacija->posaljiKod($korisnik);
            }

            return redirect()->route('verifikacija')
                ->with('status', 'Potvrdite email adresu unosom koda koji smo vam poslali.');
        }

        return redirect()->intended(route('pocetna', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('prijava')->with('status', 'Uspešno ste se odjavili.');
    }
}
