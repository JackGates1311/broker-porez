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
use App\Services\Obrasci\Ppdg3rXml;
use App\Services\Obrasci\PpOpoXml;
use App\Services\Obrasci\RasporedDela4;
use App\Services\Porezi\DividendeIzvestaj;
use App\Services\Porezi\KapitalnaDobitIzvestaj;
use App\Services\Porezi\Period;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class IzvozController extends Controller
{
    public function ppdg3r(Ppdg3rRequest $request, KapitalnaDobitIzvestaj $izvestaj, ObrazacPdf $pdf): Response
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $period = $this->polugodiste($request);

        return $pdf->preuzimanje('obrasci.ppdg-3r', [
            'period' => $period,
            'strane' => RasporedDela4::strane($this->prodaje($izvestaj, $korisnik, $period)),
            'obaveznik' => $korisnik->poreskiObaveznik ?? new PoreskiObaveznik,
            'sifre' => $this->sifrePpdg3r($request),
        ], "PPDG-3R_{$period->kod}.pdf");
    }

    public function ppdg3rXml(Ppdg3rRequest $request, KapitalnaDobitIzvestaj $izvestaj): Response
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $period = $this->polugodiste($request);
        $prodaje = $this->prodaje($izvestaj, $korisnik, $period);

        $prepreke = $prodaje->isEmpty()
            ? ["Nema prodaja u periodu „{$period->naziv()}”, pa nema šta da se prijavi."]
            : Ppdg3rXml::prepreke($prodaje);

        if ($prepreke !== []) {
            return redirect()->route('kapitalna-dobit', ['period' => $period->kod])
                ->withErrors(['xml' => ['XML za ePorezi nije napravljen:', ...$prepreke]]);
        }

        $xml = Ppdg3rXml::napravi(
            $period,
            $prodaje,
            $korisnik->poreskiObaveznik ?? new PoreskiObaveznik,
            $this->sifrePpdg3r($request),
            now('Europe/Belgrade'),
        );

        return $this->xmlPrilog($xml, "PPDG-3R_{$period->kod}.xml");
    }

    public function ppOpo(PpOpoRequest $request, int $transakcija, DividendeIzvestaj $izvestaj, ObrazacPdf $pdf): Response
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $dividenda = $this->dividenda($korisnik, $transakcija);
        $red = $izvestaj->red($dividenda);

        return $pdf->preuzimanje('obrasci.pp-opo', [
            'red' => $red,
            'obaveznik' => $korisnik->poreskiObaveznik ?? new PoreskiObaveznik,
            'sifre' => $this->sifrePpOpo($request),
        ], $this->nazivPpOpo($red, $dividenda).'.pdf');
    }

    public function ppOpoXml(PpOpoRequest $request, int $transakcija, DividendeIzvestaj $izvestaj): Response
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $dividenda = $this->dividenda($korisnik, $transakcija);
        $red = $izvestaj->red($dividenda);
        $nazad = redirect()->route('dividende', ['period' => $red->vreme->year]);

        if ($red->bez_kursa) {
            return $nazad->withErrors(['xml' => 'XML za ePorezi nije napravljen: dividenda '.($red->simbol ?? '').' od '.$red->vreme->format('d.m.Y').' nema NBS kurs.']);
        }

        $xml = PpOpoXml::napravi($red, $korisnik->poreskiObaveznik ?? new PoreskiObaveznik, $this->sifrePpOpo($request));

        if ($greske = PpOpoXml::proveri($xml)) {
            return $nazad->withErrors(['xml' => [
                'XML za ePorezi ne prolazi zvaničnu šemu Poreske uprave. Proverite podatke o poreskom obavezniku i šifre na obrascu:',
                ...$greske,
            ]]);
        }

        return $this->xmlPrilog($xml, $this->nazivPpOpo($red, $dividenda).'.xml');
    }

    private function polugodiste(Ppdg3rRequest $request): Period
    {
        return Period::za((int) $request->validated('godina'), (int) $request->validated('polugodiste'));
    }

    /**
     * @return Collection<int, object>
     */
    private function prodaje(KapitalnaDobitIzvestaj $izvestaj, Korisnik $korisnik, Period $period): Collection
    {
        return $izvestaj->za($korisnik, $period)['redovi']->filter(fn ($r) => $r->tip === TipTransakcije::Prodaja)->values();
    }

    /**
     * @return array{vrsta_prijave: string, osnov_za_prijavu: string}
     */
    private function sifrePpdg3r(Ppdg3rRequest $request): array
    {
        return [
            'vrsta_prijave' => $request->validated('vrsta_prijave') ?? config('porezi.ppdg3r.vrsta_prijave'),
            'osnov_za_prijavu' => $request->validated('osnov_za_prijavu') ?? config('porezi.ppdg3r.osnov_za_prijavu'),
        ];
    }

    /**
     * @return array{vrsta_prijave: string, sifra_vrste_prihoda: string, nacin_ostvarivanja: string}
     */
    private function sifrePpOpo(PpOpoRequest $request): array
    {
        return [
            'vrsta_prijave' => $request->validated('vrsta_prijave') ?? config('porezi.ppopo.vrsta_prijave'),
            'sifra_vrste_prihoda' => $request->validated('sifra_vrste_prihoda') ?? config('porezi.ppopo.sifra_vrste_prihoda'),
            'nacin_ostvarivanja' => $request->validated('nacin_ostvarivanja') ?? config('porezi.ppopo.nacin_ostvarivanja'),
        ];
    }

    private function dividenda(Korisnik $korisnik, int $transakcija): Transakcija
    {
        return Transakcija::query()
            ->with('imovina')
            ->where('korisnik_id', $korisnik->id)
            ->tipa(TipTransakcije::Dividenda)
            ->findOrFail($transakcija);
    }

    private function nazivPpOpo(object $red, Transakcija $dividenda): string
    {
        return 'PP-OPO_'.$red->vreme->format('Y-m-d').'_'.($red->simbol ?? $dividenda->id);
    }

    private function xmlPrilog(string $xml, string $nazivFajla): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nazivFajla}\"",
        ]);
    }
}
