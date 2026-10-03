<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistracijaRequest;
use App\Models\Korisnik;
use App\Services\VerifikacijaEmailaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistracijaController extends Controller
{
    public function create(): View
    {
        return view('auth.registracija');
    }

    public function store(RegistracijaRequest $request, VerifikacijaEmailaService $verifikacija): RedirectResponse
    {
        $korisnik = Korisnik::create([
            'korisnicko_ime' => $request->validated('korisnicko_ime'),
            'email' => $request->validated('email'),
            'lozinka_hash' => $request->validated('lozinka'),
        ]);

        Auth::login($korisnik);
        $request->session()->regenerate();

        $verifikacija->posaljiKod($korisnik);

        return redirect()->route('verifikacija')
            ->with('status', 'Nalog je kreiran. Poslali smo vam 6-cifreni kod na email.');
    }
}
