<?php

namespace App\Services\Porezi;

use App\Services\Porezi\Dto\DividendaObracun;
use App\Support\Decimal;
use BcMath\Number;

/**
 * Formule lista "Dividende" iz Excela:
 * K (bruto) = količina × dividenda po akciji (neto) + porez po odbitku
 * L (neto) = K − G;  M (bruto RSD) = K × kurs;  N (porez 15%) = M × 0,15
 * O (za uplatu) = max(0, N − porez po odbitku u RSD);  J (%) = G / round(K, 2)
 */
final class DividendaKalkulator
{
    public const STOPA = '0.15';

    public function izracunaj(
        Number $kolicina,
        Number $netoPoAkciji,
        Number $porezPoOdbitku,
        ?Number $kurs,
        ?Number $kursPoreza = null,
    ): DividendaObracun {
        $bruto = $kolicina * $netoPoAkciji + $porezPoOdbitku;
        $zaokruzenBruto = $bruto->round(2);

        $procenat = $zaokruzenBruto == Decimal::nula()
            ? Decimal::nula()
            : $porezPoOdbitku->div($zaokruzenBruto, 10);

        if ($kurs === null) {
            return new DividendaObracun($procenat, $bruto, $bruto - $porezPoOdbitku, null, null, null, null);
        }

        $brutoRsd = $bruto * $kurs;
        $porez = $brutoRsd * new Number(self::STOPA);
        $placenoRsd = $porezPoOdbitku * ($kursPoreza ?? $kurs);

        return new DividendaObracun(
            procenatPoreza: $procenat,
            bruto: $bruto,
            neto: $bruto - $porezPoOdbitku,
            brutoRsd: $brutoRsd,
            porez: $porez,
            placenPorezRsd: $placenoRsd,
            zaUplatu: Decimal::max(Decimal::nula(), $porez - $placenoRsd),
        );
    }
}
