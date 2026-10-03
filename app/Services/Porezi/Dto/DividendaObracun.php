<?php

namespace App\Services\Porezi\Dto;

use BcMath\Number;

final readonly class DividendaObracun
{
    public function __construct(
        public Number $procenatPoreza,
        public Number $bruto,
        public Number $neto,
        public ?Number $brutoRsd,
        public ?Number $porez,
        public ?Number $placenPorezRsd,
        public ?Number $zaUplatu,
    ) {}
}
