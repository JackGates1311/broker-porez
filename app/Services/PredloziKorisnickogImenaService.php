<?php

namespace App\Services;

use App\Models\Korisnik;

class PredloziKorisnickogImenaService
{
    /** Najveća dužina korisničkog imena (korisnici.korisnicko_ime VARCHAR(50)). */
    public const MAKS_DUZINA = 50;

    /**
     * Slobodna korisnička imena slična zauzetom: ime sa godinom ili brojem na kraju.
     *
     * @return list<string>
     */
    public function predlozi(string $ime, int $broj = 3): array
    {
        $sufiksi = [(string) now()->year, '_'.now()->year];

        while (count($sufiksi) < 12) {
            $sufiksi[] = (string) random_int(10, 999);
            $sufiksi[] = '_'.random_int(10, 99);
        }

        $kandidati = collect(array_unique($sufiksi))
            ->map(fn (string $sufiks) => mb_substr($ime, 0, self::MAKS_DUZINA - mb_strlen($sufiks)).$sufiks)
            ->unique();

        // Poređenje bez obzira na velika i mala slova, kao i UNIQUE indeks u MySQL-u.
        $zauzeta = Korisnik::query()
            ->whereIn('korisnicko_ime', $kandidati->all())
            ->pluck('korisnicko_ime')
            ->map(fn (string $zauzeto) => mb_strtolower($zauzeto))
            ->all();

        return $kandidati
            ->reject(fn (string $kandidat) => in_array(mb_strtolower($kandidat), $zauzeta, true))
            ->take($broj)
            ->values()
            ->all();
    }
}
