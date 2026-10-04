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
use App\Tabele\PortfolioTabela;
use Illuminate\Database\Query\Builder;
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
        $tabela = PortfolioTabela::za($request);

        return view('porezi.pregled', [
            'period' => $period,
            'godine' => $periodi->godine($korisnik),
            'kapitalnaDobit' => $kd['zbir'],
            'dividende' => $dividende->za($korisnik, $period)['zbir'],
            'tabela' => $tabela,
            'portfolio' => $tabela->primeni($this->portfolio($korisnik))->get()->map(fn ($p) => (object) [
                ...(array) $p,
                'kolicina' => Decimal::n($p->kolicina),
                'nabavna_rsd' => Decimal::n($p->nabavna_rsd),
                'bez_kursa' => (bool) $p->bez_kursa,
            ]),
            'brojPozicija' => $this->portfolio($korisnik)->count(),
            'brojBezKursa' => $kursevi->bezKursa($korisnik)->count(),
            'prodajeBezIstorije' => $kd['redovi']->filter(fn ($r) => $r->nedostaje !== null)->count(),
            'imaTransakcija' => $korisnik->transakcije()->exists(),
        ]);
    }

    /**
     * Otvoreni lotovi po imovini: preostala količina i njena nabavna vrednost u RSD.
     * Agregat je podupit "p", da bi pretraga i sortiranje (PortfolioTabela) radili nad
     * zbirnim kolonama.
     */
    private function portfolio(Korisnik $korisnik): Builder
    {
        $agregat = DB::table('poreski_lotovi as l')
            ->join('transakcije as k', 'k.id', '=', 'l.transakcija_kupovine_id')
            ->join('imovina as i', 'i.id', '=', 'l.imovina_id')
            ->where('l.korisnik_id', $korisnik->id)
            ->where('l.preostala_kolicina', '>', 0)
            ->groupBy('i.id', 'i.simbol', 'i.naziv', 'i.isin')
            ->select('i.id', 'i.simbol', 'i.naziv', 'i.isin')
            ->selectRaw('SUM(l.preostala_kolicina) as kolicina')
            ->selectRaw('SUM(CASE WHEN k.kurs IS NULL THEN 0 ELSE l.preostala_kolicina * l.nabavna_cena_po_jedinici * k.kurs END) as nabavna_rsd')
            ->selectRaw('MAX(CASE WHEN k.kurs IS NULL THEN 1 ELSE 0 END) as bez_kursa');

        return DB::query()->fromSub($agregat, 'p');
    }
}
