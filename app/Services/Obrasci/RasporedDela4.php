<?php

namespace App\Services\Obrasci;

use Illuminate\Support\Collection;

/**
 * Raspored prodaja po blokovima Dela 4 obrasca PPDG-3R.
 *
 * Na papirnom obrascu svaki blok ima jedno polje za prenos (4.1–4.6) i tačno tri reda za
 * sticanje (4.7–4.10), a strana ima devet blokova. Prodaja koja troši više od tri lota
 * nastavlja se u sledećem bloku, čija su polja 4.1–4.6 (i redni broj) prazna, pa sledeća
 * prodaja dobija sledeći redni broj u novom bloku. Tako se obrazac popunjava i ručno.
 */
class RasporedDela4
{
    public const REDOVA_STICANJA = 3;

    public const BLOKOVA_PO_STRANI = 9;

    /**
     * @param  Collection<int, object>  $prodaje  redovi KapitalnaDobitIzvestaj sa ->alokacije
     * @return list<object{redni_broj: ?int, prodaja: ?object, stavke: list<?object>}>
     */
    public static function blokovi(Collection $prodaje): array
    {
        $blokovi = [];

        foreach ($prodaje->values() as $i => $prodaja) {
            $alokacije = collect($prodaja->alokacije ?? [])->values();
            $delovi = $alokacije->isEmpty() ? [collect()] : $alokacije->chunk(self::REDOVA_STICANJA)->all();

            foreach (array_values($delovi) as $j => $deo) {
                $blokovi[] = (object) [
                    'redni_broj' => $j === 0 ? $i + 1 : null,
                    'prodaja' => $j === 0 ? $prodaja : null,
                    'stavke' => array_pad($deo->values()->all(), self::REDOVA_STICANJA, null),
                ];
            }
        }

        return $blokovi;
    }

    /**
     * Blokovi po stranama od po devet, poslednja dopunjena praznim blokovima.
     * Uvek bar jedna strana, da i prazan obrazac ima Deo 4.
     *
     * @param  Collection<int, object>  $prodaje
     * @return list<list<object{redni_broj: ?int, prodaja: ?object, stavke: list<?object>}>>
     */
    public static function strane(Collection $prodaje): array
    {
        $strane = array_chunk(self::blokovi($prodaje), self::BLOKOVA_PO_STRANI) ?: [[]];

        return array_map(fn (array $strana) => array_pad($strana, self::BLOKOVA_PO_STRANI, self::prazanBlok()), $strane);
    }

    public static function prazanBlok(): object
    {
        return (object) [
            'redni_broj' => null,
            'prodaja' => null,
            'stavke' => array_fill(0, self::REDOVA_STICANJA, null),
        ];
    }
}
