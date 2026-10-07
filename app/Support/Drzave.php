<?php

namespace App\Support;

/**
 * Države iz config/drzave.php: oznaka (RS) u bazi, "Srbija (RS)" u formi.
 */
class Drzave
{
    /**
     * Sve države po srpskom abecednom redu (č, ć, đ, š, ž posle osnovnog slova).
     *
     * @return array<string, string> oznaka => naziv
     */
    public static function sve(): array
    {
        $drzave = config('drzave');
        $kljuc = fn (string $naziv) => strtr(mb_strtolower($naziv), ['č' => 'c{', 'ć' => 'c|', 'đ' => 'd{', 'š' => 's{', 'ž' => 'z{']);

        uasort($drzave, fn (string $a, string $b) => strcmp($kljuc($a), $kljuc($b)));

        return $drzave;
    }

    /**
     * "Srbija (RS)" za poznatu oznaku; inače vraća vrednost kakva jeste.
     */
    public static function prikaz(?string $vrednost): ?string
    {
        $naziv = config('drzave.'.$vrednost);

        return is_string($naziv) && $vrednost !== null ? "{$naziv} ({$vrednost})" : $vrednost;
    }

    /**
     * Oznaka iz unosa "Srbija (RS)", "RS" ili "srbija"; null ako država nije prepoznata.
     */
    public static function oznaka(?string $unos): ?string
    {
        $unos = trim((string) $unos);

        if (preg_match('/\(([A-Za-z]{2})\)$/', $unos, $poklapanje) === 1) {
            $unos = $poklapanje[1];
        }

        $oznaka = mb_strtoupper($unos);

        if (array_key_exists($oznaka, config('drzave'))) {
            return $oznaka;
        }

        $poNazivu = array_search(mb_strtolower($unos), array_map('mb_strtolower', config('drzave')), true);

        return $poNazivu === false ? null : $poNazivu;
    }
}
