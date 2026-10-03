<?php

namespace App\Http\Controllers\Porezi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Porezi\UvozKursevaRequest;
use App\Http\Requests\Porezi\UvozTrading212Request;
use App\Models\Korisnik;
use App\Services\Kursevi\NbsCsvParser;
use App\Services\Kursevi\NbsKursService;
use App\Services\Kursevi\PrimenaKurseva;
use App\Services\Uvoz\Trading212UvozService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UvozController extends Controller
{
    public function index(Request $request, PrimenaKurseva $kursevi): View
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        return view('porezi.uvoz', [
            'transakcije' => $korisnik->transakcije()
                ->with('imovina')
                ->orderByDesc('vreme_utc')
                ->orderByDesc('id')
                ->paginate(50),
            'bezKursa' => $kursevi->bezKursa($korisnik)->with('imovina')->orderBy('vreme_utc')->get(),
        ]);
    }

    public function trading212(UvozTrading212Request $request, Trading212UvozService $uvoz): RedirectResponse
    {
        $rezime = $uvoz->uvezi($request->user(), $request->file('fajlovi'));

        return redirect()->route('uvoz')
            ->with('status', $rezime->poruka())
            ->with('greske_uvoza', $rezime->greske);
    }

    public function kursevi(UvozKursevaRequest $request, NbsCsvParser $parser, NbsKursService $kursevi, PrimenaKurseva $primena): RedirectResponse
    {
        $fajl = $request->file('fajl');

        try {
            $lista = $parser->parsiraj($fajl->getRealPath(), $fajl->getClientOriginalName());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['fajl' => $e->getMessage()]);
        }

        $broj = $kursevi->sacuvajIzListe($lista);
        $primena->primeni($request->user());

        return redirect()->route('uvoz')->with('status', "Sačuvano kurseva: {$broj}. Obračun je osvežen.");
    }

    public function osveziKurseve(Request $request, PrimenaKurseva $primena): RedirectResponse
    {
        $neuspesni = $primena->preuzmiIPrimeni($request->user());

        return redirect()->route('uvoz')->with('status', $neuspesni === []
            ? 'Kursevi su preuzeti i obračun je osvežen.'
            : 'Kurs nije preuzet za: '.implode(', ', $neuspesni).'. Pokušajte ponovo ili uvezite NBS kursnu listu.');
    }

    public function obrisiSve(Request $request): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        DB::transaction(function () use ($korisnik) {
            DB::table('alokacije_lotova_prodaje')->where('korisnik_id', $korisnik->id)->delete();
            DB::table('poreski_lotovi')->where('korisnik_id', $korisnik->id)->delete();
            DB::table('transakcije')->where('korisnik_id', $korisnik->id)->delete();
        });

        return redirect()->route('uvoz')->with('status', 'Sve transakcije su obrisane.');
    }
}
