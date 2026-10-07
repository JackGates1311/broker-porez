<?php

namespace App\Http\Controllers\Porezi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Porezi\PoreskiObaveznikRequest;
use App\Models\Korisnik;
use App\Models\PoreskiObaveznik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoreskiObaveznikController extends Controller
{
    public function edit(Request $request): View
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $obaveznik = $korisnik->poreskiObaveznik ?? new PoreskiObaveznik(['email' => $korisnik->email, 'tip_obaveznika' => 1]);

        return view('porezi.poreski-obaveznik', [
            'obaveznik' => $obaveznik,
            'popunjen' => $obaveznik->exists && $obaveznik->jePopunjen(),
        ]);
    }

    public function update(PoreskiObaveznikRequest $request): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $biloPopunjeno = (bool) $korisnik->poreskiObaveznik?->jePopunjen();

        $korisnik->poreskiObaveznik()->updateOrCreate([], $request->podaci());

        // Prvi unos otključava ostatak aplikacije, pa se korisnik vodi na pregled.
        return redirect()->route($biloPopunjeno ? 'poreski-obaveznik' : 'pocetna')
            ->with('status', 'Podaci o poreskom obavezniku su sačuvani.');
    }
}
