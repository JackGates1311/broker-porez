<?php

namespace App\Http\Controllers\Porezi;

use App\Http\Controllers\Controller;
use App\Models\Korisnik;
use App\Services\Porezi\DividendeIzvestaj;
use App\Services\Porezi\DostupniPeriodi;
use App\Services\Porezi\Period;
use App\Tabele\DividendeTabela;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DividendeController extends Controller
{
    public function __invoke(Request $request, DividendeIzvestaj $izvestaj, DostupniPeriodi $periodi): View
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $period = Period::izKoda($request->query('period'));

        $tabela = DividendeTabela::za($request);

        return view('porezi.dividende', [
            ...$izvestaj->za($korisnik, $period, $tabela),
            'tabela' => $tabela,
            'period' => $period,
            'godine' => $periodi->godine($korisnik),
            'profilPopunjen' => (bool) $korisnik->poreskiProfil?->jePopunjen(),
        ]);
    }
}
