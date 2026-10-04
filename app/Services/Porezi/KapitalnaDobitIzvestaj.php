<?php

namespace App\Services\Porezi;

use App\Enums\TipTransakcije;
use App\Models\Korisnik;
use App\Models\Transakcija;
use App\Support\Decimal;
use App\Support\Tabela\Tabela;
use BcMath\Number;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Redovi i zbirovi lista "Kapitalna Dobit" iz Excela, nad tabelama
 * transakcije / poreski_lotovi / alokacije_lotova_prodaje.
 */
class KapitalnaDobitIzvestaj
{
    /**
     * Zbir se uvek računa nad celim periodom (to je poreska obaveza); $tabela samo bira
     * i ređa redove koji se prikazuju, pretragom i sortiranjem u SQL-u.
     *
     * @return array{redovi: Collection<int, object>, zbir: array<string, Number|int>, ukupno: int}
     */
    public function za(Korisnik $korisnik, Period $period, ?Tabela $tabela = null): array
    {
        $transakcije = $this->upit($korisnik, $period)
            ->with('imovina')
            ->orderBy('transakcije.vreme_utc')
            ->orderBy('transakcije.id')
            ->get();
        $alokacije = $this->alokacije($korisnik, $transakcije->pluck('id'));
        $preostalo = DB::table('poreski_lotovi')
            ->where('korisnik_id', $korisnik->id)
            ->whereIn('transakcija_kupovine_id', $transakcije->pluck('id'))
            ->pluck('preostala_kolicina', 'transakcija_kupovine_id');

        $stopa = new Number(config('porezi.stopa'));
        $redovi = $transakcije->map(function (Transakcija $t) use ($alokacije, $preostalo, $stopa) {
            $kolicina = $t->decimal('kolicina');
            $kurs = $t->decimal('kurs');
            $vrednost = $kurs === null ? null : $kolicina * $t->decimal('cena_po_akciji') * $kurs;
            $red = (object) [
                'transakcija' => $t,
                'tip' => $t->tip(),
                'vreme' => $t->vremeSrb(),
                'simbol' => $t->imovina?->simbol,
                'naziv' => $t->imovina?->naziv,
                'kolicina' => $kolicina,
                'cena' => $t->decimal('cena_po_akciji'),
                'valuta' => $t->valuta_cene,
                'kurs' => $kurs,
                'vrednost_rsd' => $vrednost,
                'preostalo' => isset($preostalo[$t->id]) ? Decimal::n($preostalo[$t->id]) : null,
                'nabavna_rsd' => null,
                'dobit' => null,
                'porez' => null,
                'obaveza' => null,
                'alokacije' => collect(),
                'nedostaje' => null,
                'bez_kursa' => $kurs === null,
            ];

            if ($red->tip === TipTransakcije::Prodaja) {
                $red->alokacije = $alokacije->get($t->id, collect());
                $red->nabavna_rsd = $red->alokacije->reduce(fn (Number $z, $a) => $z + $a->nabavna_rsd, Decimal::nula());
                $uzeto = $red->alokacije->reduce(fn (Number $z, $a) => $z + $a->kolicina, Decimal::nula());
                $red->nedostaje = $kolicina > $uzeto ? $kolicina - $uzeto : null;
                $red->bez_kursa = $red->bez_kursa || $red->alokacije->contains(fn ($a) => $a->kurs === null);

                if ($vrednost !== null) {
                    $red->dobit = $vrednost - $red->nabavna_rsd;
                    $red->porez = $red->dobit * $stopa;
                    $red->obaveza = Decimal::max(Decimal::nula(), $red->porez);
                }
            }

            return $red;
        });

        return [
            'redovi' => $tabela?->izaberi(
                $this->upit($korisnik, $period)->leftJoin('imovina as i', 'i.id', '=', 'transakcije.imovina_id'),
                'transakcije.id',
                $redovi->keyBy(fn ($r) => $r->transakcija->id),
            ) ?? $redovi,
            'zbir' => $this->zbir($redovi),
            'ukupno' => $redovi->count(),
        ];
    }

    /**
     * Kupovine i prodaje hartija u periodu.
     *
     * @return Builder<Transakcija>
     */
    private function upit(Korisnik $korisnik, Period $period): Builder
    {
        $upit = Transakcija::query()
            ->where('transakcije.korisnik_id', $korisnik->id)
            ->whereNotNull('transakcije.imovina_id')
            ->tipa(TipTransakcije::Kupovina, TipTransakcije::Prodaja);

        if ($granice = $period->utcGranice()) {
            $upit->whereBetween('transakcije.vreme_utc', $granice);
        }

        return $upit;
    }

    /**
     * Zbirovi kao u Excelu (kolone P i Q). "Trenutni dug" se računa kao max(0, ΣN):
     * Excelova formula ΣN − max(0, |ΣN<0| − ΣN>0) daje −2×gubitak kada je zbir negativan.
     *
     * @param  Collection<int, object>  $redovi
     * @return array<string, Number|int>
     */
    private function zbir(Collection $redovi): array
    {
        $nula = Decimal::nula();
        $zbir = [
            'broj_prodaja' => 0,
            'prodajna_rsd' => $nula,
            'nabavna_rsd' => $nula,
            'dobici' => $nula,
            'gubici' => $nula,
            'porez' => $nula,
            'obaveza_po_prodajama' => $nula,
        ];

        foreach ($redovi as $red) {
            if ($red->tip !== TipTransakcije::Prodaja || $red->dobit === null) {
                continue;
            }

            $zbir['broj_prodaja']++;
            $zbir['prodajna_rsd'] += $red->vrednost_rsd;
            $zbir['nabavna_rsd'] += $red->nabavna_rsd;
            $zbir['porez'] += $red->porez;
            $zbir['obaveza_po_prodajama'] += $red->obaveza;

            if ($red->dobit >= $nula) {
                $zbir['dobici'] += $red->dobit;
            } else {
                $zbir['gubici'] += -$red->dobit;
            }
        }

        $zbir['neto_dobit'] = $zbir['dobici'] - $zbir['gubici'];
        $zbir['preostalo_za_prebijanje'] = Decimal::max($nula, $zbir['gubici'] - $zbir['dobici']);
        $zbir['trenutni_dug'] = Decimal::max($nula, $zbir['porez']);

        return $zbir;
    }

    /**
     * Alokacije prodaja (lotovi iz kojih se prodaja namiruje), grupisane po prodaji.
     *
     * @param  Collection<int, int>  $prodajeId
     * @return Collection<int, Collection<int, object>>
     */
    private function alokacije(Korisnik $korisnik, Collection $prodajeId): Collection
    {
        return DB::table('alokacije_lotova_prodaje as a')
            ->join('poreski_lotovi as l', 'l.id', '=', 'a.poreski_lot_id')
            ->join('transakcije as k', 'k.id', '=', 'l.transakcija_kupovine_id')
            ->where('a.korisnik_id', $korisnik->id)
            ->whereIn('a.transakcija_prodaje_id', $prodajeId)
            ->orderBy('k.vreme_utc')
            ->orderBy('k.id')
            ->get([
                'a.transakcija_prodaje_id',
                'a.iskoriscena_kolicina',
                'a.nabavna_vrednost_rsd',
                'k.vreme_utc',
                'k.broker_transakcija_id',
                'k.cena_po_akciji',
                'k.valuta_cene',
                'k.kurs',
            ])
            ->map(fn ($a) => (object) [
                'prodaja_id' => $a->transakcija_prodaje_id,
                'kolicina' => Decimal::n($a->iskoriscena_kolicina),
                'nabavna_rsd' => Decimal::n($a->nabavna_vrednost_rsd),
                'vreme' => CarbonImmutable::parse($a->vreme_utc, 'UTC')->setTimezone('Europe/Belgrade'),
                'broker_id' => $a->broker_transakcija_id,
                'cena' => Decimal::n($a->cena_po_akciji),
                'valuta' => $a->valuta_cene,
                'kurs' => $a->kurs === null ? null : Decimal::n($a->kurs),
            ])
            ->groupBy('prodaja_id');
    }
}
