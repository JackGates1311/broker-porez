<?php

namespace App\Http\Controllers\Porezi;

use App\Enums\TipTransakcije;
use App\Http\Controllers\Controller;
use App\Http\Requests\Porezi\UvozRucniAkcijeRequest;
use App\Http\Requests\Porezi\UvozRucniFajlRequest;
use App\Http\Requests\Porezi\UvozRucniFormatRequest;
use App\Http\Requests\Porezi\UvozRucniKoloneRequest;
use App\Http\Requests\Porezi\UvozRucniUveziRequest;
use App\Models\Korisnik;
use App\Models\SablonUvoza;
use App\Services\Uvoz\IzvorUvoza;
use App\Services\Uvoz\Rucni\CarobnjakUvoza;
use App\Services\Uvoz\Rucni\CsvCitac;
use App\Services\Uvoz\Rucni\PodesavanjaUvoza;
use App\Services\Uvoz\Rucni\PoljeUvoza;
use App\Services\Uvoz\Rucni\PredlogMapiranja;
use App\Services\Uvoz\Rucni\RucniCsvParser;
use App\Services\Uvoz\UvozService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Čarobnjak za CSV brokera koji aplikacija ne poznaje: 1 fajl → 2 format → 3 kolone
 * → 4 akcije → 5 pregled i uvoz. Svaki korak čita fajl iznova po podešavanjima iz
 * sesije; korak čiji preduslovi nisu ispunjeni vraća na prvi nepotpun korak.
 */
class UvozRucniController extends Controller
{
    private const KORACI = [
        2 => 'uvoz.rucni.format',
        3 => 'uvoz.rucni.kolone',
        4 => 'uvoz.rucni.akcije',
        5 => 'uvoz.rucni.pregled',
    ];

    /** Više različitih vrednosti akcije znači da je verovatno izabrana pogrešna kolona. */
    private const NAJVISE_AKCIJA = 200;

    public function __construct(
        private readonly CsvCitac $citac,
        private readonly PredlogMapiranja $predlog,
    ) {}

    public function zapocni(UvozRucniFajlRequest $request): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $fajl = $request->file('fajl_rucni');
        $sablon = $request->filled('sablon_id') ? $korisnik->sabloniUvoza()->findOrFail($request->integer('sablon_id')) : null;

        $podesavanja = $sablon?->podesavanjaUvoza()
            ?? new PodesavanjaUvoza(separator: $this->citac->detektujSeparator($fajl->getRealPath()));

        CarobnjakUvoza::zapocni($request->session(), $fajl, $podesavanja, $sablon);

        // Sa šablonom se ide pravo na pregled; pregled sam vraća na korak koji fajl ne zadovoljava.
        return redirect()->route($sablon !== null ? 'uvoz.rucni.pregled' : 'uvoz.rucni.format');
    }

    public function format(Request $request): View|RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        $p = $carobnjak->podesavanja();

        return view('porezi.uvoz-rucni.format', [
            'korak' => 2,
            'dostupno' => $this->prviNepotpun($carobnjak),
            'carobnjak' => $carobnjak,
            'p' => $p,
            'pocetak' => $this->citac->pocetak($carobnjak->putanja(), $p, 10 + $p->redZaglavlja),
        ]);
    }

    public function sacuvajFormat(UvozRucniFormatRequest $request): RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        $carobnjak->sacuvaj($carobnjak->podesavanja()->sa($request->safe()->only([
            'separator', 'kodna_strana', 'red_zaglavlja', 'decimalni_separator', 'separator_hiljada',
        ])));

        // Promena polja (data-auto-submit) samo osvežava pregled; "Dalje" ide na sledeći korak.
        if ($request->input('dalje') === null) {
            return redirect()->route('uvoz.rucni.format');
        }

        $p = $carobnjak->podesavanja();

        // Prvi prolaz bez šablona: kolone se predlažu po nazivima zaglavlja.
        if ($p->kolone === []) {
            $zaglavlje = $this->citac->procitaj($carobnjak->putanja(), $p, 1)['zaglavlje'];
            $carobnjak->sacuvaj($p->sa(['kolone' => $this->predlog->kolone($zaglavlje)]));
        }

        return redirect()->route('uvoz.rucni.kolone');
    }

    public function kolone(Request $request): View|RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        $p = $carobnjak->podesavanja();
        ['zaglavlje' => $zaglavlje, 'redovi' => $redovi] = $this->citac->procitaj($carobnjak->putanja(), $p, 1);

        if ($zaglavlje === []) {
            return redirect()->route('uvoz.rucni.format')->withErrors(['red_zaglavlja' => 'Fajl nema red zaglavlja na izabranom mestu.']);
        }

        $prviRed = $redovi === [] ? [] : array_values($redovi)[0];

        return view('porezi.uvoz-rucni.kolone', [
            'korak' => 3,
            'dostupno' => $this->prviNepotpun($carobnjak),
            'carobnjak' => $carobnjak,
            'p' => $p,
            'zaglavlje' => $zaglavlje,
            'primer' => count($prviRed) === count($zaglavlje) ? array_combine($zaglavlje, $prviRed) : [],
            'nedostaju' => $p->nedostajuceKolone($zaglavlje),
        ]);
    }

    public function sacuvajKolone(UvozRucniKoloneRequest $request): RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        $p = $carobnjak->podesavanja();
        $zaglavlje = $this->citac->procitaj($carobnjak->putanja(), $p, 1)['zaglavlje'];
        $kolone = [];

        foreach (PoljeUvoza::cases() as $polje) {
            $kolona = $request->input("kolone.{$polje->value}.kolona");
            $konstanta = $polje->dozvoljavaKonstantu() ? $request->input("kolone.{$polje->value}.konstanta") : null;

            if (filled($kolona) && in_array($kolona, $zaglavlje, true)) {
                $kolone[$polje->value] = ['kolona' => $kolona];
            } elseif (filled($konstanta)) {
                $kolone[$polje->value] = ['konstanta' => strtoupper($konstanta)];
            }
        }

        $novo = $p->sa([
            'kolone' => $kolone,
            'format_datuma' => $request->validated('format_datuma'),
            'vremenska_zona' => $request->validated('vremenska_zona'),
        ]);

        if (! $novo->imaObaveznaPolja()) {
            return back()->withInput()->withErrors(['kolone' => 'Izaberite postojeće kolone za datum i akciju.']);
        }

        $carobnjak->sacuvaj($novo);

        return redirect()->route('uvoz.rucni.akcije');
    }

    public function akcije(Request $request): View|RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        if (($nazad = $this->preduslov($carobnjak, 4)) !== null) {
            return $nazad;
        }

        $p = $carobnjak->podesavanja();
        $vrednosti = $this->vrednostiAkcija($carobnjak);

        if (count($vrednosti) > self::NAJVISE_AKCIJA) {
            return redirect()->route('uvoz.rucni.kolone')->withErrors([
                'kolone.akcija.kolona' => 'Kolona akcije ima više od '.self::NAJVISE_AKCIJA.' različitih vrednosti. Da li je izabrana prava kolona?',
            ]);
        }

        $tipovi = [];

        foreach ($vrednosti as $vrednost => $broj) {
            $vrednost = (string) $vrednost;
            $tipovi[$vrednost] = TipTransakcije::tryFrom($p->akcije[$vrednost] ?? '') ?? $this->predlog->tip($vrednost);
        }

        return view('porezi.uvoz-rucni.akcije', [
            'korak' => 4,
            'dostupno' => $this->prviNepotpun($carobnjak),
            'carobnjak' => $carobnjak,
            'vrednosti' => $vrednosti,
            'tipovi' => $tipovi,
        ]);
    }

    public function sacuvajAkcije(UvozRucniAkcijeRequest $request): RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        // Ranije mapirane vrednosti (iz šablona) ostaju, da šablon važi i za druge izvode.
        $akcije = $carobnjak->podesavanja()->akcije;

        foreach ($request->validated('akcije') as $stavka) {
            $akcije[$stavka['vrednost']] = $stavka['tip'];
        }

        $carobnjak->sacuvaj($carobnjak->podesavanja()->sa(['akcije' => $akcije]));

        return redirect()->route('uvoz.rucni.pregled');
    }

    public function pregled(Request $request): View|RedirectResponse
    {
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        if (($nazad = $this->preduslov($carobnjak, 5)) !== null) {
            return $nazad;
        }

        $parser = new RucniCsvParser($carobnjak->podesavanja(), $this->citac);
        $redovi = $parser->parsiraj($carobnjak->putanja(), $carobnjak->nazivFajla());
        $poTipu = [];

        foreach ($redovi as $red) {
            $poTipu[$red->tip->value] = ($poTipu[$red->tip->value] ?? 0) + 1;
        }

        return view('porezi.uvoz-rucni.pregled', [
            'korak' => 5,
            'dostupno' => 5,
            'carobnjak' => $carobnjak,
            'redovi' => array_slice($redovi, 0, 20),
            'ukupno' => count($redovi),
            'poTipu' => $poTipu,
            'greske' => $parser->greske(),
        ]);
    }

    public function uvezi(UvozRucniUveziRequest $request, UvozService $uvoz): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $carobnjak = CarobnjakUvoza::iz($request->session());

        if ($carobnjak === null) {
            return $this->istekla();
        }

        if (($nazad = $this->preduslov($carobnjak, 5)) !== null) {
            return $nazad;
        }

        $podesavanja = $carobnjak->podesavanja();
        $rezime = $uvoz->uvezi($korisnik, new RucniCsvParser($podesavanja, $this->citac), [
            $carobnjak->putanja() => $carobnjak->nazivFajla(),
        ]);

        $poruka = $rezime->poruka();

        if ($request->boolean('sacuvaj_sablon')) {
            $naziv = trim($request->validated('naziv_sablona'));
            $korisnik->sabloniUvoza()->updateOrCreate(['naziv' => $naziv], ['podesavanja' => $podesavanja->uNiz()]);
            $poruka .= " Šablon „{$naziv}” je sačuvan.";
        }

        $carobnjak->zavrsi();

        return redirect()->route('uvoz', ['izvor' => IzvorUvoza::Rucni->kod()])
            ->with('status', $poruka)
            ->with('greske_uvoza', $rezime->greske);
    }

    public function odustani(Request $request): RedirectResponse
    {
        CarobnjakUvoza::iz($request->session())?->zavrsi();

        return redirect()->route('uvoz', ['izvor' => IzvorUvoza::Rucni->kod()]);
    }

    public function obrisiSablon(Request $request, int $sablon): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        /** @var SablonUvoza $model */
        $model = $korisnik->sabloniUvoza()->findOrFail($sablon);
        $model->delete();

        return redirect()->route('uvoz', ['izvor' => IzvorUvoza::Rucni->kod()])
            ->with('status', "Šablon „{$model->naziv}” je obrisan.");
    }

    /**
     * Prvi korak koji još nije završen (5 = sve je spremno za pregled).
     */
    private function prviNepotpun(CarobnjakUvoza $carobnjak): int
    {
        $p = $carobnjak->podesavanja();
        $zaglavlje = $this->citac->procitaj($carobnjak->putanja(), $p, 1)['zaglavlje'];

        if (count($zaglavlje) < 2) {
            return 2;
        }

        if (! $p->imaObaveznaPolja() || $p->nedostajuceKolone($zaglavlje) !== []) {
            return 3;
        }

        foreach (array_keys($this->vrednostiAkcija($carobnjak)) as $vrednost) {
            if (! isset($p->akcije[(string) $vrednost])) {
                return 4;
            }
        }

        return 5;
    }

    private function preduslov(CarobnjakUvoza $carobnjak, int $korak): ?RedirectResponse
    {
        $dostupno = $this->prviNepotpun($carobnjak);

        return $dostupno < $korak
            ? redirect()->route(self::KORACI[$dostupno])->with('status', $this->razlog($dostupno))
            : null;
    }

    private function razlog(int $korak): string
    {
        return match ($korak) {
            2 => 'Proverite format fajla: zaglavlje mora imati bar dve kolone.',
            3 => 'Mapiranje kolona nije potpuno ili fajl nema neke kolone iz šablona.',
            default => 'Fajl ima vrednosti akcije koje još nisu mapirane na tip.',
        };
    }

    /**
     * @return array<string, int>
     */
    private function vrednostiAkcija(CarobnjakUvoza $carobnjak): array
    {
        $p = $carobnjak->podesavanja();
        $kolona = $p->kolonaZa(PoljeUvoza::Akcija);

        return $kolona === null ? [] : $this->citac->vrednostiKolone($carobnjak->putanja(), $p, $kolona);
    }

    private function istekla(): RedirectResponse
    {
        return redirect()->route('uvoz', ['izvor' => IzvorUvoza::Rucni->kod()])
            ->withErrors(['fajl_rucni' => 'Sesija uvoza je istekla, izaberite fajl ponovo.']);
    }
}
