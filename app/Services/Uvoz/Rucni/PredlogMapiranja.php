<?php

namespace App\Services\Uvoz\Rucni;

use App\Enums\TipTransakcije;
use Illuminate\Support\Str;

/**
 * Početni predlozi u čarobnjaku: koja kolona odgovara kom polju (po nazivu zaglavlja)
 * i koji tip odgovara vrednosti akcije (po ključnim rečima). Korisnik ih uvek potvrđuje.
 */
final class PredlogMapiranja
{
    /**
     * @param  list<string>  $zaglavlje
     * @return array<string, array{kolona: string}> PoljeUvoza value => kolona
     */
    public function kolone(array $zaglavlje): array
    {
        $normalizovano = [];

        foreach ($zaglavlje as $kolona) {
            $normalizovano[$kolona] = self::normalizuj($kolona);
        }

        $predlog = [];
        $zauzete = [];

        foreach (PoljeUvoza::cases() as $polje) {
            // Prvi predlog sa liste ima prednost (npr. "Time (UTC)" pre "Date").
            foreach ($polje->predloziZaglavlja() as $trazeno) {
                $kolona = array_search($trazeno, array_diff_key($normalizovano, $zauzete), true);

                if ($kolona !== false) {
                    $predlog[$polje->value] = ['kolona' => (string) $kolona];
                    $zauzete[$kolona] = true;

                    break;
                }
            }
        }

        return $predlog;
    }

    public function tip(string $akcija): TipTransakcije
    {
        $akcija = strtolower(Str::ascii($akcija));
        $sadrzi = fn (string ...$reci) => preg_match('/\b('.implode('|', array_map(fn ($r) => preg_quote($r, '/'), $reci)).')/', $akcija) === 1;

        return match (true) {
            $sadrzi('dividend', 'div', 'dividenda') => TipTransakcije::Dividenda,
            $sadrzi('buy', 'bought', 'purchase', 'kupovina', 'kupi') => TipTransakcije::Kupovina,
            $sadrzi('sell', 'sold', 'sale', 'prodaja', 'prodaj') => TipTransakcije::Prodaja,
            $sadrzi('deposit', 'uplata', 'depozit') => TipTransakcije::Depozit,
            default => TipTransakcije::Ostalo,
        };
    }

    public static function normalizuj(string $naziv): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($naziv)));
    }
}
