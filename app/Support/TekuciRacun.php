<?php

namespace App\Support;

/**
 * Broj tekućeg računa u Srbiji: 3 cifre banke, 13 cifara partije i 2 kontrolne
 * cifre (ISO 7064 MOD 97-10). Čuva se kao 18 cifara bez crtica.
 */
final class TekuciRacun
{
    /**
     * "265-0000000360051-46", "265-360051-46" (skraćen zapis, partija se dopunjuje
     * nulama sleva) ili 18 cifara; razmaci su dozvoljeni. Inače null.
     */
    public static function normalizuj(?string $unos): ?string
    {
        $unos = preg_replace('/\s+/', '', (string) $unos);

        if (preg_match('/^\d{18}$/', $unos)) {
            return $unos;
        }

        if (preg_match('/^(\d{3})-(\d{1,13})-(\d{2})$/', $unos, $m)) {
            return $m[1].str_pad($m[2], 13, '0', STR_PAD_LEFT).$m[3];
        }

        return null;
    }

    public static function kontrolniBrojJeIspravan(string $racun): bool
    {
        if (! preg_match('/^\d{18}$/', $racun)) {
            return false;
        }

        $ostatak = 0;
        foreach (str_split(substr($racun, 0, 16).'00') as $cifra) {
            $ostatak = ($ostatak * 10 + (int) $cifra) % 97;
        }

        return 98 - $ostatak === (int) substr($racun, 16);
    }

    /**
     * Prikaz sa crticama: 265-0000000360051-46.
     */
    public static function prikaz(?string $racun): string
    {
        if ($racun === null || ! preg_match('/^(\d{3})(\d{13})(\d{2})$/', $racun, $m)) {
            return (string) $racun;
        }

        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
}
