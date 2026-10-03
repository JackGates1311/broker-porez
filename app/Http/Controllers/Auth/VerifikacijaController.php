<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifikacijaRequest;
use App\Models\Korisnik;
use App\Services\VerifikacijaEmailaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerifikacijaController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        if ($korisnik->jeVerifikovan()) {
            return redirect()->route('pocetna');
        }

        return view('auth.verifikacija', ['email' => $korisnik->email]);
    }

    public function store(VerifikacijaRequest $request, VerifikacijaEmailaService $verifikacija): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        if ($korisnik->jeVerifikovan()) {
            return redirect()->route('pocetna');
        }

        if (! $verifikacija->proveriKod($korisnik, $request->validated('kod'))) {
            throw ValidationException::withMessages([
                'kod' => 'Kod nije ispravan ili je istekao. Pokušajte ponovo ili zatražite novi kod.',
            ]);
        }

        return redirect()->intended(route('pocetna', absolute: false))
            ->with('status', 'Email adresa je uspešno potvrđena.');
    }

    public function ponovoPosalji(Request $request, VerifikacijaEmailaService $verifikacija): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        if ($korisnik->jeVerifikovan()) {
            return redirect()->route('pocetna');
        }

        $poslato = RateLimiter::attempt(
            'verifikacija-ponovo:'.$korisnik->getKey(),
            maxAttempts: 3,
            callback: fn () => $verifikacija->posaljiKod($korisnik),
        );

        if (! $poslato) {
            $sekunde = RateLimiter::availableIn('verifikacija-ponovo:'.$korisnik->getKey());

            return back()->withErrors([
                'throttle' => "Previše zahteva za novi kod. Pokušajte ponovo za {$sekunde} s.",
            ]);
        }

        return back()->with('status', 'Novi kod je poslat na vaš email.');
    }
}
