<?php

namespace App\Services\Porezi\Dto;

use BcMath\Number;

final readonly class FifoRezultat
{
    /**
     * @param  list<FifoLot>  $lotovi
     * @param  list<FifoAlokacija>  $alokacije
     * @param  array<int, Number>  $nedostajuceKolicine  prodaja id => količina bez pokrića u kupovinama
     * @param  list<int>  $prodajeBezKursaKupovine  prodaje čiji bar jedan lot nema NBS kurs
     */
    public function __construct(
        public array $lotovi,
        public array $alokacije,
        public array $nedostajuceKolicine,
        public array $prodajeBezKursaKupovine,
    ) {}
}
