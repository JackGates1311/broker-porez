<?php

namespace App\Support;

use BcMath\Number;

/**
 * Pomoćne funkcije za decimalnu aritmetiku bez float-ova (BcMath\Number).
 */
final class Decimal
{
    public static function n(Number|string|int|null $vrednost): Number
    {
        if ($vrednost instanceof Number) {
            return $vrednost;
        }

        $vrednost = trim((string) $vrednost);

        return new Number($vrednost === '' ? '0' : $vrednost);
    }

    /**
     * Parsira broj iz CSV-a; prazna vrednost daje null.
     */
    public static function izTeksta(?string $vrednost): ?Number
    {
        $vrednost = trim((string) $vrednost, " \t\n\r\0\x0B\"");

        if ($vrednost === '') {
            return null;
        }

        if (! preg_match('/^[+-]?\d+(\.\d+)?$/', $vrednost)) {
            throw new \InvalidArgumentException("Neispravan broj: {$vrednost}");
        }

        return new Number(ltrim($vrednost, '+'));
    }

    public static function nula(): Number
    {
        return new Number('0');
    }

    public static function max(Number $a, Number $b): Number
    {
        return $a >= $b ? $a : $b;
    }

    public static function min(Number $a, Number $b): Number
    {
        return $a <= $b ? $a : $b;
    }

    /**
     * Normalizovan zapis za upis u DECIMAL(28,10) kolonu.
     */
    public static function zaBazu(?Number $vrednost): ?string
    {
        return $vrednost === null ? null : (string) $vrednost->round(10);
    }

    /**
     * Kanonski zapis bez suvišnih nula (za ključeve i prikaz količina).
     */
    public static function kanonski(?Number $vrednost): string
    {
        if ($vrednost === null) {
            return '';
        }

        $tekst = (string) $vrednost;

        if (str_contains($tekst, '.')) {
            $tekst = rtrim(rtrim($tekst, '0'), '.');
        }

        return $tekst === '-0' ? '0' : $tekst;
    }

    /**
     * Srpski format: 1.519,96
     */
    public static function format(?Number $vrednost, int $decimale = 2): string
    {
        if ($vrednost === null) {
            return '—';
        }

        $zaokruzeno = (string) $vrednost->round($decimale);
        $negativan = str_starts_with($zaokruzeno, '-');
        [$ceo, $deo] = array_pad(explode('.', ltrim($zaokruzeno, '-')), 2, '');

        $ceo = strrev(implode('.', str_split(strrev($ceo), 3)));
        $deo = str_pad($deo, $decimale, '0');

        $tekst = $decimale > 0 ? "{$ceo},{$deo}" : $ceo;

        return ($negativan && trim($tekst, '0.,') !== '' ? '-' : '').$tekst;
    }

    /**
     * Količina akcija: do 8 decimala, bez suvišnih nula.
     */
    public static function formatKolicina(?Number $vrednost): string
    {
        if ($vrednost === null) {
            return '—';
        }

        $tekst = self::kanonski($vrednost->round(8));
        [$ceo, $deo] = array_pad(explode('.', $tekst), 2, '');

        return self::format(new Number($ceo), 0).($deo !== '' ? ','.$deo : '');
    }
}
