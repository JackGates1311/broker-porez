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

    /**
     * Uslovi za SQL upit nad kolonom tip_akcije (LIKE obrasci).
     *
     * @return list<string>
     */
    public function sqlObrasci(): array
    {
        return match ($this) {
            self::Kupovina => ['% buy'],
            self::Prodaja => ['% sell'],
            self::Dividenda => ['Dividend%'],
            self::Depozit => ['Deposit'],
            self::Ostalo => [],
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
     * Isti uslov kao sqlObrasci(), kao raw SQL sa bindinzima (za CASE u sortiranju tabela).
     *
     * @return array{0: string, 1: list<string>}
     */
    public function sqlUslov(string $kolona): array
    {
        if ($this === self::Ostalo) {
            $obrasci = array_merge(...array_map(fn (self $t) => $t->sqlObrasci(), self::cases()));

            return ['NOT ('.implode(' OR ', array_fill(0, count($obrasci), "{$kolona} LIKE ?")).')', $obrasci];
        }

        $obrasci = $this->sqlObrasci();

        return ['('.implode(' OR ', array_fill(0, count($obrasci), "{$kolona} LIKE ?")).')', $obrasci];
    }
}
