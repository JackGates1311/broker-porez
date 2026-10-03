<?php

namespace App\Services\Porezi\Dto;

use App\Enums\TipTransakcije;
use BcMath\Number;

final readonly class FifoTransakcija
{
    public function __construct(
        public int $id,
        public int|string $imovinaId,
        public TipTransakcije $tip,
        public Number $kolicina,
        public Number $cenaPoAkciji,
        public ?Number $kurs,
    ) {}
}
