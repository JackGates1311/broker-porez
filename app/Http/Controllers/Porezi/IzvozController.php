<?php

namespace App\Http\Controllers\Porezi;

use App\Enums\TipTransakcije;
use App\Http\Controllers\Controller;
use App\Http\Requests\Porezi\Ppdg3rRequest;
use App\Http\Requests\Porezi\PpOpoRequest;
use App\Models\Korisnik;
use App\Models\PoreskiObaveznik;
use App\Models\Transakcija;
use App\Services\Obrasci\ObrazacPdf;
use App\Services\Porezi\DividendeIzvestaj;
use App\Services\Porezi\KapitalnaDobitIzvestaj;
use App\Services\Porezi\Period;
use Symfony\Component\HttpFoundation\Response;

class IzvozController extends Controller
{
    public function ppdg3r(Ppdg3rRequest $request, KapitalnaDobitIzvestaj $izvestaj, ObrazacPdf $pdf): Response
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $period = Period::za((int) $request->validated('godina'), (int) $request->validated('polugodiste'));
        $obracun = $izvestaj->za($korisnik, $period);

        return $pdf->preuzimanje('obrasci.ppdg-3r', [
            'period' => $period,
            'prodaje' => $obracun['redovi']->filter(fn ($r) => $r->tip === TipTransakcije::Prodaja)->values(),
            'zbir' => $obracun['zbir'],
            'obaveznik' => $korisnik->poreskiObaveznik ?? new PoreskiObaveznik,
            'sifre' => [
                'vrsta_prijave' => $request->validated('vrsta_prijave') ?? config('porezi.ppdg3r.vrsta_prijave'),
                'osnov_za_prijavu' => $request->validated('osnov_za_prijavu') ?? config('porezi.ppdg3r.osnov_za_prijavu'),
            ],
        ], "PPDG-3R_{$period->kod}.pdf");
    }

    public function ppOpo(PpOpoRequest $request, int $transakcija, DividendeIzvestaj $izvestaj, ObrazacPdf $pdf): Response
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        $dividenda = Transakcija::query()
            ->with('imovina')
            ->where('korisnik_id', $korisnik->id)
            ->tipa(TipTransakcije::Dividenda)
            ->findOrFail($transakcija);

        $red = $izvestaj->red($dividenda);

        return $pdf->preuzimanje('obrasci.pp-opo', [
            'red' => $red,
            'obaveznik' => $korisnik->poreskiObaveznik ?? new PoreskiObaveznik,
            'sifre' => [
                'vrsta_prijave' => $request->validated('vrsta_prijave') ?? config('porezi.ppopo.vrsta_prijave'),
                'sifra_vrste_prihoda' => $request->validated('sifra_vrste_prihoda') ?? config('porezi.ppopo.sifra_vrste_prihoda'),
                'nacin_ostvarivanja' => $request->validated('nacin_ostvarivanja') ?? config('porezi.ppopo.nacin_ostvarivanja'),
            ],
        ], 'PP-OPO_'.$red->vreme->format('Y-m-d').'_'.($red->simbol ?? $dividenda->id).'.pdf');
    }
}
