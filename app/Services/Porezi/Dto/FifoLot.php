<?php

namespace App\Services\Porezi\Dto;

use BcMath\Number;

final class FifoLot
{
    public function __construct(
        public readonly int $transakcijaKupovineId,
        public readonly int|string $imovinaId,
        public readonly Number $pocetnaKolicina,
        public Number $preostalaKolicina,
        public readonly Number $nabavnaCenaPoJedinici,
        public readonly ?Number $kurs,
    ) {}
}
