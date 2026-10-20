<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Važeća verzija Uslova korišćenja i Politike privatnosti (config/pravno.php).
 */
class Pravno
{
    public static function verzija(): string
    {
        return (string) config('pravno.verzija');
    }

    public static function vaziOd(): CarbonImmutable
    {
        return CarbonImmutable::parse(config('pravno.vazi_od'), 'Europe/Belgrade')->startOfDay();
    }

    /** Datum stupanja na snagu za prikaz, npr. 20.10.2026. */
    public static function vaziOdPrikaz(): string
    {
        return self::vaziOd()->format('d.m.Y.');
    }

    /**
     * Da li prihvaćena verzija još važi: važeća je, ili je ranija a nova još nije stupila na snagu.
     */
    public static function vaziPrihvacena(?string $verzija): bool
    {
        if ($verzija === null) {
            return false;
        }

        return $verzija === self::verzija() || CarbonImmutable::now()->lt(self::vaziOd());
    }
}
