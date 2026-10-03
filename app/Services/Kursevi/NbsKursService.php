<?php

namespace App\Services\Kursevi;

use App\Models\Kurs;
use App\Support\Decimal;
use BcMath\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Srednji kurs NBS po datumu primene, iz tabele kursevi. Kursevi koji nedostaju
 * preuzimaju se sa javnog API-ja (config porezi.kurs_api_url).
 */
class NbsKursService
{
    /** @var array<string, Number|null> */
    private array $kes = [];

    public function kurs(?string $valuta, string $datum): ?Number
    {
        if ($valuta === null || $valuta === '') {
            return null;
        }

        [$valuta, $delilac] = $this->normalizujValutu($valuta);

        if ($valuta === 'RSD') {
            return new Number('1');
        }

        $kljuc = "{$datum}|{$valuta}";

        if (! array_key_exists($kljuc, $this->kes)) {
            $vrednost = Kurs::query()->where('datum', $datum)->where('valuta', $valuta)->value('srednji_kurs');
            $this->kes[$kljuc] = $vrednost === null ? null : Decimal::n($vrednost);
        }

        return $this->kes[$kljuc]?->div($delilac, 10);
    }

    /**
     * Preuzima sa API-ja sve parove (datum, valuta) koji nedostaju u tabeli kursevi.
     *
     * @param  iterable<array{0: string, 1: string}>  $parovi  [Y-m-d, valuta]
     * @return list<string> datumi koje nije bilo moguće preuzeti
     */
    public function obezbediKurseve(iterable $parovi): array
    {
        $trazeno = [];

        foreach ($parovi as [$datum, $valuta]) {
            [$valuta] = $this->normalizujValutu($valuta);

            if ($valuta !== 'RSD' && $valuta !== '') {
                $trazeno[$datum][$valuta] = true;
            }
        }

        if ($trazeno === []) {
            return [];
        }

        $postojeci = Kurs::query()
            ->whereIn('datum', array_keys($trazeno))
            ->get(['datum', 'valuta'])
            ->groupBy(fn (Kurs $k) => $k->datum->format('Y-m-d'));

        $neuspesni = [];

        foreach ($trazeno as $datum => $valute) {
            $nedostaju = array_diff(array_keys($valute), $postojeci->get($datum)?->pluck('valuta')->all() ?? []);

            if ($nedostaju === []) {
                continue;
            }

            try {
                $this->sacuvaj($datum, $this->preuzmi($datum), $nedostaju);
            } catch (Throwable $e) {
                report($e);
                $neuspesni[] = $datum;
            }
        }

        $this->kes = [];

        return $neuspesni;
    }

    /**
     * Upis kurseva iz NBS CSV-a (upsert po datumu i valuti).
     *
     * @param  list<array{datum: string, valuta: string, kurs: Number}>  $kursevi
     */
    public function sacuvajIzListe(array $kursevi): int
    {
        $redovi = array_map(fn (array $k) => [
            'datum' => $k['datum'],
            'valuta' => $k['valuta'],
            'srednji_kurs' => Decimal::zaBazu($k['kurs']),
        ], $kursevi);

        foreach (array_chunk($redovi, 500) as $deo) {
            DB::table('kursevi')->upsert($deo, ['datum', 'valuta'], ['srednji_kurs']);
        }

        $this->kes = [];

        return count($redovi);
    }

    /**
     * @return array<string, Number> valuta => kurs za 1 jedinicu
     */
    protected function preuzmi(string $datum): array
    {
        $odgovor = Http::timeout(config('porezi.kurs_api_timeout'))
            ->retry(2, 500)
            ->acceptJson()
            ->get(rtrim(config('porezi.kurs_api_url'), '/')."/rates/{$datum}")
            ->throw()
            ->json('rates', []);

        $kursevi = [];

        foreach ($odgovor as $red) {
            if (! isset($red['code'], $red['exchange_middle'])) {
                continue;
            }

            $paritet = (string) ($red['parity'] ?? 1);
            $kursevi[strtoupper($red['code'])] = Decimal::n(sprintf('%.6F', $red['exchange_middle']))
                ->div($paritet, 10);
        }

        return $kursevi;
    }

    /**
     * @param  array<string, Number>  $kursevi
     * @param  list<string>  $valute
     */
    private function sacuvaj(string $datum, array $kursevi, array $valute): void
    {
        $lista = [];

        foreach ($valute as $valuta) {
            if (isset($kursevi[$valuta])) {
                $lista[] = ['datum' => $datum, 'valuta' => $valuta, 'kurs' => $kursevi[$valuta]];
            }
        }

        $this->sacuvajIzListe($lista);
    }

    /**
     * Trading 212 cene britanskih akcija daje u penijima (GBX).
     *
     * @return array{0: string, 1: string}
     */
    private function normalizujValutu(string $valuta): array
    {
        $valuta = strtoupper(trim($valuta));

        return $valuta === 'GBX' ? ['GBP', '100'] : [$valuta, '1'];
    }
}
