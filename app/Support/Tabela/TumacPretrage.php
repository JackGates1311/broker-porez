<?php

namespace App\Support\Tabela;

use BcMath\Number;
use Carbon\CarbonImmutable;

/**
 * Tumači tekst globalne pretrage kao broj ili datum, u zapisu koji korisnik vidi
 * u tabelama (srpski: 1.519,96 i 15.03.2026.), bez float-ova.
 */
final class TumacPretrage
{
    /**
     * Mogući brojevi u tekstu, sa brojem unetih decimala. "1.519" je dvosmisleno
     * (hiljadu petsto devetnaest ili jedan zarez petsto devetnaest), pa vraća oba.
     *
     * @return list<array{0: Number, 1: int}>
     */
    public static function brojevi(string $tekst): array
    {
        $tekst = str_replace(' ', '', trim($tekst));
        $kandidati = [];

        if (preg_match('/^[+-]?\d{1,3}(\.\d{3})+(,\d+)?$/', $tekst) || preg_match('/^[+-]?\d+,\d+$/', $tekst)) {
            // Srpski zapis: tačka razdvaja hiljade, zarez decimale.
            $kandidati[] = str_replace(['.', ','], ['', '.'], $tekst);
        }

        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $tekst)) {
            $kandidati[] = $tekst;
        }

        $brojevi = [];

        foreach (array_unique($kandidati) as $kandidat) {
            $kandidat = ltrim($kandidat, '+');
            $decimale = str_contains($kandidat, '.') ? strlen(substr($kandidat, strpos($kandidat, '.') + 1)) : 0;
            $brojevi[] = [new Number($kandidat), $decimale];
        }

        return $brojevi;
    }

    public static function ceoBroj(string $tekst): ?string
    {
        $tekst = trim($tekst);

        return preg_match('/^[+-]?\d{1,18}$/', $tekst) ? ltrim($tekst, '+') : null;
    }

    /**
     * Datum, mesec ili godina kao poluotvoren opseg [od, do) u UTC-u, za kolonu koja
     * čuva UTC vreme, a prikazuje se u zoni $zona.
     *
     * Podržano: 15.03.2026(.), 03.2026(.), 2026(.), 2026-03-15, 2026-03.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function opsegDatuma(string $tekst, string $zona): ?array
    {
        $tekst = rtrim(trim($tekst), '.');

        [$godina, $mesec, $dan] = match (true) {
            (bool) preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $tekst, $m) => [(int) $m[3], (int) $m[2], (int) $m[1]],
            (bool) preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $tekst, $m) => [(int) $m[1], (int) $m[2], (int) $m[3]],
            (bool) preg_match('/^(\d{1,2})\.(\d{4})$/', $tekst, $m) => [(int) $m[2], (int) $m[1], null],
            (bool) preg_match('/^(\d{4})-(\d{1,2})$/', $tekst, $m) => [(int) $m[1], (int) $m[2], null],
            (bool) preg_match('/^(\d{4})$/', $tekst, $m) => [(int) $m[1], null, null],
            default => [null, null, null],
        };

        if ($godina === null || $godina < 1900 || $godina > 2999) {
            return null;
        }

        if ($mesec !== null && ($mesec < 1 || $mesec > 12)) {
            return null;
        }

        if ($dan !== null && ! checkdate($mesec, $dan, $godina)) {
            return null;
        }

        $od = CarbonImmutable::create($godina, $mesec ?? 1, $dan ?? 1, 0, 0, 0, $zona);
        $do = match (true) {
            $dan !== null => $od->addDay(),
            $mesec !== null => $od->addMonth(),
            default => $od->addYear(),
        };

        return [
            $od->utc()->format('Y-m-d H:i:s'),
            $do->utc()->format('Y-m-d H:i:s'),
        ];
    }
}
