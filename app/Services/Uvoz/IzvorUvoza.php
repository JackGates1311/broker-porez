<?php

namespace App\Services\Uvoz;

/**
 * Odakle je transakcija uvezena (kolona transakcije.izvor). Redosled slučajeva je
 * redosled u padajućoj listi na tabu Uvoz.
 */
enum IzvorUvoza: string
{
    case Trading212 = 'TRADING212';
    case Ibkr = 'IBKR';
    case Rucni = 'RUCNI';

    public function naziv(): string
    {
        return match ($this) {
            self::Trading212 => 'Trading 212',
            self::Ibkr => 'Interactive Brokers',
            self::Rucni => 'Drugi broker (CSV)',
        };
    }

    /**
     * Izvor za koji uvoz još nije implementiran prikazuje se kao "uskoro".
     */
    public function dostupan(): bool
    {
        return $this !== self::Ibkr;
    }

    /**
     * Vrednost u URL-u (?izvor=…).
     */
    public function kod(): string
    {
        return strtolower($this->value);
    }

    /**
     * Izvor iz URL-a; nepoznat ili nedostupan izvor vraća podrazumevani (Trading 212).
     */
    public static function izKoda(?string $kod): self
    {
        $izvor = self::tryFrom(strtoupper((string) $kod));

        return $izvor !== null && $izvor->dostupan() ? $izvor : self::Trading212;
    }
}
