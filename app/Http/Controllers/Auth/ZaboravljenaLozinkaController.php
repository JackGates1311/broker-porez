<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ZaboravljenaLozinkaRequest;
use App\Services\ResetLozinkeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ZaboravljenaLozinkaController extends Controller
{
    private const MAKS_ZAHTEVA = 3;

    private const PERIOD_SEKUNDI = 600;

    public function create(): View
    {
        return view('auth.zaboravljena-lozinka');
    }

    public function store(ZaboravljenaLozinkaRequest $request, ResetLozinkeService $reset): RedirectResponse
    {
        $email = $request->validated('email');
        $kljuc = 'reset-lozinke:'.Str::transliterate($email.'|'.$request->ip());

        $poslato = RateLimiter::attempt(
            $kljuc,
            maxAttempts: self::MAKS_ZAHTEVA,
            callback: fn () => $reset->posaljiLink($email),
            decaySeconds: self::PERIOD_SEKUNDI,
        );

        if (! $poslato) {
            $sekunde = RateLimiter::availableIn($kljuc);

            return redirect()->route('lozinka.poslato')
                ->with('email', $email)
                ->withErrors(['throttle' => "Previše zahteva za resetovanje lozinke. Pokušajte ponovo za {$sekunde} s."]);
        }

        // Ista poruka bez obzira na to da li nalog postoji.
        return redirect()->route('lozinka.poslato')
            ->with('email', $email)
            ->with('poslato', true);
    }

    public function poslato(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('email');

        if ($email === null) {
            return redirect()->route('lozinka.zaboravljena');
        }

        return view('auth.zaboravljena-lozinka-poslato', ['email' => $email]);
    }
}
