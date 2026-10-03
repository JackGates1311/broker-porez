<?php

namespace App\Services\Uvoz;

use App\Enums\TipTransakcije;
use BcMath\Number;
use Carbon\CarbonImmutable;

final readonly class UvezeniRed
{
    public function __construct(
        public string $akcija,
        public TipTransakcije $tip,
        public CarbonImmutable $vremeUtc,
        public ?string $isin,
        public ?string $simbol,
        public ?string $naziv,
        public ?string $brokerId,
        public ?string $napomena,
        public ?Number $kolicina,
        public ?Number $cenaPoAkciji,
        public ?string $valutaCene,
        public Number $ukupno,
        public string $valutaUkupno,
        public ?Number $porezPoOdbitku,
        public ?string $valutaPoreza,
        public ?Number $provizija,
        public ?string $valutaProvizije,
        public string $jedinstveniKljuc,
    ) {}

    /**
     * Datum po srpskom vremenu: po njemu se uzima NBS kurs (kao u Excelu).
     */
    public function datumSrb(): CarbonImmutable
    {
        return $this->vremeUtc->setTimezone('Europe/Belgrade')->startOfDay();
    }
}
