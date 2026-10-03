<?php

namespace App\Http\Controllers\Porezi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Porezi\PoreskiProfilRequest;
use App\Models\Korisnik;
use App\Models\PoreskiProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoreskiProfilController extends Controller
{
    public function edit(Request $request): View
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        return view('porezi.profil', [
            'profil' => $korisnik->poreskiProfil ?? new PoreskiProfil(['email' => $korisnik->email, 'zemlja_rezidentstva' => 'RS']),
        ]);
    }

    public function update(PoreskiProfilRequest $request): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        $korisnik->poreskiProfil()->updateOrCreate([], $request->validated());

        return redirect()->route('profil')->with('status', 'Poreski profil je sačuvan.');
    }
}
