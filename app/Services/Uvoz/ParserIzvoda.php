<?php

namespace App\Services\Uvoz;

/**
 * Čita izvod jednog brokera u redove nezavisne od brokera. Red koji ne može da se
 * pročita preskače se i opisuje u greske(); fajl koji uopšte nije tog formata baca
 * InvalidArgumentException.
 */
interface ParserIzvoda
{
    /**
     * @return list<UvezeniRed>
     */
    public function parsiraj(string $putanja, string $nazivFajla = ''): array;

    /**
     * Greške iz poslednjeg poziva parsiraj().
     *
     * @return list<string>
     */
    public function greske(): array;
}
