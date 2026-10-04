<?php

namespace App\Services\Porezi;

use App\Enums\TipTransakcije;
use App\Models\Korisnik;
use App\Models\Transakcija;
use App\Support\Decimal;
use App\Support\Tabela\Tabela;
use BcMath\Number;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Redovi i zbirovi lista "Dividende" iz Excela.
 */
class DividendeIzvestaj
{
    public function __construct(private readonly DividendaKalkulator $kalkulator) {}

    /**
     * Zbir se uvek računa nad celim periodom; $tabela samo bira i ređa prikazane redove (SQL).
     *
     * @return array{redovi: Collection<int, object>, zbir: array<string, Number|int>, ukupno: int}
     */
    public function za(Korisnik $korisnik, Period $period, ?Tabela $tabela = null): array
    {
        $redovi = $this->upit($korisnik, $period)
            ->with('imovina')
            ->orderBy('transakcije.vreme_utc')
            ->orderBy('transakcije.id')
            ->get()
            ->map(fn (Transakcija $t) => $this->red($t));

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
     * @return Builder<Transakcija>
     */
    private function upit(Korisnik $korisnik, Period $period): Builder
    {
        $upit = Transakcija::query()
            ->where('transakcije.korisnik_id', $korisnik->id)
            ->tipa(TipTransakcije::Dividenda);

        if ($granice = $period->utcGranice()) {
            $upit->whereBetween('transakcije.vreme_utc', $granice);
        }

        return $upit;
    }

    public function red(Transakcija $t): object
    {
        $obracun = $this->kalkulator->izracunaj(
            $t->decimal('kolicina'),
            $t->decimal('cena_po_akciji'),
            $t->decimal('porez_po_odbitku') ?? Decimal::nula(),
            $t->decimal('kurs'),
            $t->decimal('kurs_porez'),
        );

        return (object) [
            'transakcija' => $t,
            'vreme' => $t->vremeSrb(),
            'simbol' => $t->imovina?->simbol,
            'naziv' => $t->imovina?->naziv,
            'isin' => $t->imovina?->isin,
            'kolicina' => $t->decimal('kolicina'),
            'neto_po_akciji' => $t->decimal('cena_po_akciji'),
            'porez_po_odbitku' => $t->decimal('porez_po_odbitku'),
            'valuta' => $t->valuta_cene,
            'kurs' => $t->decimal('kurs'),
            'obracun' => $obracun,
            'bez_kursa' => $obracun->brutoRsd === null,
        ];
    }

    /**
     * @param  Collection<int, object>  $redovi
     * @return array<string, Number|int>
     */
    private function zbir(Collection $redovi): array
    {
        $nula = Decimal::nula();
        $zbir = ['broj' => 0, 'bruto_rsd' => $nula, 'porez' => $nula, 'placen_porez_rsd' => $nula, 'za_uplatu' => $nula];

        foreach ($redovi as $red) {
            if ($red->bez_kursa) {
                continue;
            }

            $zbir['broj']++;
            $zbir['bruto_rsd'] += $red->obracun->brutoRsd;
            $zbir['porez'] += $red->obracun->porez;
            $zbir['placen_porez_rsd'] += $red->obracun->placenPorezRsd;
            $zbir['za_uplatu'] += $red->obracun->zaUplatu;
        }

        return $zbir;
    }
}
