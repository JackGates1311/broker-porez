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
            'nedostajeZaXml' => $obaveznik->nedostajeZaXml(),
            'prihvatioUslove' => $korisnik->prihvatioUslove(),
            'ranijePrihvatio' => $korisnik->uslovi_verzija !== null,
        ]);
    }

    public function update(PoreskiObaveznikRequest $request): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $biloPopunjeno = $korisnik->poreskiObaveznik?->jePopunjen() && $korisnik->prihvatioUslove();

        $korisnik->poreskiObaveznik()->updateOrCreate([], $request->podaci());

        if (! $korisnik->prihvatioUslove()) {
            $korisnik->prihvatiUslove();
        }

        // Prvi unos otključava ostatak aplikacije, pa se korisnik vodi na pregled.
        return redirect()->route($biloPopunjeno ? 'poreski-obaveznik' : 'pocetna')
            ->with('status', 'Podaci o poreskom obavezniku su sačuvani.');
    }
}
