<?php

namespace App\Http\Controllers\Porezi;

use App\Http\Controllers\Controller;
use App\Models\Korisnik;
use App\Services\Porezi\DostupniPeriodi;
use App\Services\Porezi\KapitalnaDobitIzvestaj;
use App\Services\Porezi\Period;
use App\Tabele\KapitalnaDobitTabela;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KapitalnaDobitController extends Controller
{
    public function __invoke(Request $request, KapitalnaDobitIzvestaj $izvestaj, DostupniPeriodi $periodi): View
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $period = Period::izKoda($request->query('period'));
        $godine = $periodi->godine($korisnik);

        // Podrazumevano polugodište za PPDG-3R: izabrano, ili poslednje završeno.
        $prijava = $period->jePolugodiste()
            ? $period
            : Period::za(now('Europe/Belgrade')->subMonths(6)->year, now('Europe/Belgrade')->subMonths(6)->month <= 6 ? 1 : 2);

        $tabela = KapitalnaDobitTabela::za($request);

        return view('porezi.kapitalna-dobit', [
            ...$izvestaj->za($korisnik, $period, $tabela),
            'tabela' => $tabela,
            'period' => $period,
            'godine' => $godine,
            'prijava' => $prijava,
            'profilPopunjen' => (bool) $korisnik->poreskiProfil?->jePopunjen(),
        ]);
    }
}
