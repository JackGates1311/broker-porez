<?php

namespace App\Tabele;

use App\Enums\TipTransakcije;

/**
 * Delovi SQL izraza za izračunate kolone. Kolona::izraz() nema bindinge, pa se ovde
 * u SQL ugrađuju samo konstante iz koda (LIKE obrasci tipa, poreska stopa), uz proveru.
 */
final class SqlIzraz
{
    public static function jeTipa(TipTransakcije $tip, string $kolona = 'transakcije.tip_akcije'): string
    {
        $obrasci = array_map(fn (string $o) => "{$kolona} LIKE ".self::tekst($o), $tip->sqlObrasci());

        return $obrasci === [] ? '1 = 0' : '('.implode(' OR ', $obrasci).')';
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
        if (! preg_match('/^[\w %]+$/u', $tekst)) {
            throw new \InvalidArgumentException("Nedozvoljen znak u SQL konstanti: {$tekst}");
        }

        return "'{$tekst}'";
    }
}
