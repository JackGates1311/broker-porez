<?php

namespace App\Enums;

enum TipTransakcije: string
{
    case Kupovina = 'KUPOVINA';
    case Prodaja = 'PRODAJA';
    case Dividenda = 'DIVIDENDA';
    case Depozit = 'DEPOZIT';
    case Ostalo = 'OSTALO';

    /**
     * Klasifikuje sirovu vrednost kolone "Action" iz Trading 212 CSV-a.
     */
    public static function izAkcije(string $akcija): self
    {
        $akcija = strtolower(trim($akcija));

        return match (true) {
            str_ends_with($akcija, ' buy') => self::Kupovina,
            str_ends_with($akcija, ' sell') => self::Prodaja,
            str_starts_with($akcija, 'dividend') => self::Dividenda,
            $akcija === 'deposit' => self::Depozit,
            default => self::Ostalo,
        };
    }

    public function naziv(): string
    {
        return match ($this) {
            self::Kupovina => 'Kupovina',
            self::Prodaja => 'Prodaja',
            self::Dividenda => 'Dividenda',
            self::Depozit => 'Depozit',
            self::Ostalo => 'Ostalo',
        };
    }

    /**
     * Uslov nad kolonom tip kao raw SQL sa bindinzima (za enumeraciju u tabelama).
     *
     * @return array{0: string, 1: list<string>}
     */
    public function sqlUslov(string $kolona): array
    {
        return ["{$kolona} = ?", [$this->value]];
    }
}
