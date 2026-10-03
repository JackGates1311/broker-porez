<?php

namespace App\Http\Controllers\Porezi;

use App\Http\Controllers\Controller;
use App\Models\Korisnik;
use App\Services\Kursevi\PrimenaKurseva;
use App\Services\Porezi\DividendeIzvestaj;
use App\Services\Porezi\DostupniPeriodi;
use App\Services\Porezi\KapitalnaDobitIzvestaj;
use App\Services\Porezi\Period;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        KapitalnaDobitIzvestaj $kapitalnaDobit,
        DividendeIzvestaj $dividende,
        DostupniPeriodi $periodi,
        PrimenaKurseva $kursevi,
    ): View {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $period = Period::izKoda($request->query('period'));
        $kd = $kapitalnaDobit->za($korisnik, $period);

        return view('porezi.pregled', [
            'period' => $period,
            'godine' => $periodi->godine($korisnik),
            'kapitalnaDobit' => $kd['zbir'],
            'dividende' => $dividende->za($korisnik, $period)['zbir'],
            'portfolio' => $this->portfolio($korisnik),
            'brojBezKursa' => $kursevi->bezKursa($korisnik)->count(),
            'prodajeBezIstorije' => $kd['redovi']->filter(fn ($r) => $r->nedostaje !== null)->count(),
            'imaTransakcija' => $korisnik->transakcije()->exists(),
        ]);
    }

    /**
     * Otvoreni lotovi po imovini: preostala količina i njena nabavna vrednost u RSD.
     *
     * @return list<object>
     */
    private function portfolio(Korisnik $korisnik): array
    {
        $lotovi = DB::table('poreski_lotovi as l')
            ->join('transakcije as k', 'k.id', '=', 'l.transakcija_kupovine_id')
            ->join('imovina as i', 'i.id', '=', 'l.imovina_id')
            ->where('l.korisnik_id', $korisnik->id)
            ->where('l.preostala_kolicina', '>', 0)
            ->orderBy('i.simbol')
            ->get(['i.id', 'i.simbol', 'i.naziv', 'i.isin', 'l.preostala_kolicina', 'l.nabavna_cena_po_jedinici', 'k.kurs', 'k.valuta_cene']);

        $poImovini = [];

        foreach ($lotovi as $lot) {
            $stavka = $poImovini[$lot->id] ??= (object) [
                'simbol' => $lot->simbol,
                'naziv' => $lot->naziv,
                'isin' => $lot->isin,
                'valuta' => $lot->valuta_cene,
                'kolicina' => Decimal::nula(),
                'nabavna_rsd' => Decimal::nula(),
                'bez_kursa' => false,
            ];

            $kolicina = Decimal::n($lot->preostala_kolicina);
            $stavka->kolicina += $kolicina;

            if ($lot->kurs === null) {
                $stavka->bez_kursa = true;
            } else {
                $stavka->nabavna_rsd += $kolicina * Decimal::n($lot->nabavna_cena_po_jedinici) * Decimal::n($lot->kurs);
            }
        }

        return array_values($poImovini);
    }
}
