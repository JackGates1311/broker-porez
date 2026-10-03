<?php

namespace App\Services\Porezi;

use App\Enums\TipTransakcije;
use App\Models\Korisnik;
use App\Models\Transakcija;
use App\Support\Decimal;
use BcMath\Number;
use Illuminate\Support\Collection;

/**
 * Redovi i zbirovi lista "Dividende" iz Excela.
 */
class DividendeIzvestaj
{
    public function __construct(private readonly DividendaKalkulator $kalkulator) {}

    /**
     * @return array{redovi: Collection<int, object>, zbir: array<string, Number|int>}
     */
    public function za(Korisnik $korisnik, Period $period): array
    {
        $upit = Transakcija::query()
            ->with('imovina')
            ->where('korisnik_id', $korisnik->id)
            ->tipa(TipTransakcije::Dividenda)
            ->orderBy('vreme_utc')
            ->orderBy('id');

        if ($granice = $period->utcGranice()) {
            $upit->whereBetween('vreme_utc', $granice);
        }

        $redovi = $upit->get()->map(fn (Transakcija $t) => $this->red($t));

        return ['redovi' => $redovi, 'zbir' => $this->zbir($redovi)];
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
