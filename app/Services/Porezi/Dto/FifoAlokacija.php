<?php

namespace App\Services\Porezi\Dto;

use BcMath\Number;

final readonly class FifoAlokacija
{
    public function __construct(
        public int $transakcijaProdajeId,
        public int $transakcijaKupovineId,
        public Number $kolicina,
        public Number $nabavnaVrednostRsd,
    ) {}
}
