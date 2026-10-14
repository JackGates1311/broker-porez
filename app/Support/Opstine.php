<?php

namespace App\Support;

/**
 * Opštine iz config/opstine.php (šifre trezora, polje 2.4 PP OPO): šifra (223) u bazi,
 * "Grad Novi Sad (223)" u formi.
 */
class Opstine
{
    /**
     * Sve opštine po srpskom abecednom redu, gradovi među njima po imenu (bez "Grad ").
     *
     * @return array<string, string> šifra => naziv
     */
    public static function sve(): array
    {
        $opstine = config('opstine');
        $kljuc = fn (string $naziv) => strtr(mb_strtolower(preg_replace('/^Grad /', '', $naziv)), ['č' => 'c{', 'ć' => 'c|', 'đ' => 'd{', 'š' => 's{', 'ž' => 'z{']);

        uasort($opstine, fn (string $a, string $b) => strcmp($kljuc($a), $kljuc($b)));

        return $opstine;
    }

    /**
     * "Grad Novi Sad (223)" za poznatu šifru; inače vraća vrednost kakva jeste.
     */
    public static function prikaz(?string $sifra): ?string
    {
        $naziv = config('opstine.'.$sifra);

        return is_string($naziv) && $sifra !== null ? "{$naziv} ({$sifra})" : $sifra;
    }

    /**
     * Šifra iz unosa "Grad Novi Sad (223)", "223", "Novi Sad" ili "grad novi sad";
     * null ako opština nije prepoznata.
     */
    public static function sifra(?string $unos): ?string
    {
        $unos = trim((string) $unos);

        if (preg_match('/\((\d{3})\)$/', $unos, $poklapanje) === 1) {
            $unos = $poklapanje[1];
        }

        if (array_key_exists($unos, config('opstine'))) {
            return $unos;
        }

        $bezGrad = fn (string $naziv) => mb_strtolower(preg_replace('/^grad /i', '', trim($naziv)));
        foreach (config('opstine') as $sifra => $naziv) {
            if ($bezGrad($naziv) === $bezGrad($unos)) {
                return (string) $sifra;
            }
        }

        return null;
    }
}
