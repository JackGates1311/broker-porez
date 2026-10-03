<?php

namespace App\Services\Uvoz;

final readonly class UvozRezime
{
    /**
     * @param  list<string>  $greske
     * @param  list<string>  $datumiBezKursa
     */
    public function __construct(
        public int $procitano,
        public int $uvezeno,
        public int $duplikati,
        public array $greske,
        public array $datumiBezKursa,
    ) {}

    public function poruka(): string
    {
        $poruka = "Uvezeno novih transakcija: {$this->uvezeno} (pročitano {$this->procitano}, već postojalo {$this->duplikati}).";

        if ($this->datumiBezKursa !== []) {
            $poruka .= ' Kurs nije preuzet za: '.implode(', ', $this->datumiBezKursa).'.';
        }

        return $poruka;
    }
}
