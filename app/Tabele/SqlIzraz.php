<?php

namespace App\Tabele;

use App\Enums\TipTransakcije;

/**
 * Delovi SQL izraza za izračunate kolone. Kolona::izraz() nema bindinge, pa se ovde
 * u SQL ugrađuju samo konstante iz koda (vrednost tipa, poreska stopa), uz proveru.
 */
final class SqlIzraz
{
    public static function jeTipa(TipTransakcije $tip, string $kolona = 'transakcije.tip'): string
    {
        return "{$kolona} = ".self::tekst($tip->value);
    }

    public static function broj(string $broj): string
    {
        if (! preg_match('/^\d+(\.\d+)?$/', $broj)) {
            throw new \InvalidArgumentException("Nije broj: {$broj}");
        }

        return $broj;
    }

    private static function tekst(string $tekst): string
    {
        if (! preg_match('/^\w+$/u', $tekst)) {
            throw new \InvalidArgumentException("Nedozvoljen znak u SQL konstanti: {$tekst}");
        }

        return "'{$tekst}'";
    }
}
