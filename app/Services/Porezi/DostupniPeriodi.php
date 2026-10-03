<?php

namespace App\Services\Porezi;

use App\Models\Korisnik;
use Carbon\CarbonImmutable;

class DostupniPeriodi
{
    /**
     * Godine (najnovija prva) za koje korisnik ima transakcije.
     *
     * @return list<int>
     */
    public function godine(Korisnik $korisnik): array
    {
        $granice = $korisnik->transakcije()->selectRaw('MIN(vreme_utc) as od, MAX(vreme_utc) as do')->first();

        if ($granice?->od === null) {
            return [];
        }

        $od = CarbonImmutable::parse($granice->od, 'UTC')->setTimezone('Europe/Belgrade')->year;
        $do = CarbonImmutable::parse($granice->do, 'UTC')->setTimezone('Europe/Belgrade')->year;

        return range($do, $od);
    }
}
