<?php

namespace App\Support\Tabela;

use App\Support\Decimal;
use BcMath\Number;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Definicija kolone tabele: naslov, tip, SQL izraz za pretragu i sortiranje i prikaz ćelije.
 *
 * SQL izrazi dolaze isključivo iz koda (definicije tabela), nikad iz zahteva; vrednosti iz
 * pretrage idu kroz binding.
 */
final class Kolona
{
    private ?string $izraz = null;

    /** @var list<string> */
    private array $dodatniIzrazi = [];

    private int $decimale = 2;

    private string $zona = 'UTC';

    /** @var list<array{vrednost: string, naziv: string, uslov: string, bindings: list<mixed>}> */
    private array $opcije = [];

    private bool $sortabilna = true;

    private bool $pretraziva = true;

    private bool $skrivenNaslov = false;

    private string $klasa = '';

    private ?Closure $prikaz = null;

    private function __construct(
        public readonly string $kljuc,
        public readonly string $naslov,
        public readonly TipKolone $tip,
    ) {
        if ($tip === TipKolone::Akcija) {
            $this->sortabilna = false;
            $this->pretraziva = false;
        }

        if (in_array($tip, [TipKolone::Ceo, TipKolone::Decimalni], true)) {
            $this->klasa = 'broj';
        }
    }

    public static function tekst(string $kljuc, string $naslov): self
    {
        return new self($kljuc, $naslov, TipKolone::Tekst);
    }

    public static function ceo(string $kljuc, string $naslov): self
    {
        return (new self($kljuc, $naslov, TipKolone::Ceo))->decimale(0);
    }

    public static function decimalni(string $kljuc, string $naslov, int $decimale = 2): self
    {
        return (new self($kljuc, $naslov, TipKolone::Decimalni))->decimale($decimale);
    }

    public static function datum(string $kljuc, string $naslov, string $zona = 'Europe/Belgrade'): self
    {
        $kolona = new self($kljuc, $naslov, TipKolone::Datum);
        $kolona->zona = $zona;

        return $kolona;
    }

    public static function enumeracija(string $kljuc, string $naslov): self
    {
        return new self($kljuc, $naslov, TipKolone::Enum);
    }

    public static function akcija(string $kljuc, string $naslov): self
    {
        return new self($kljuc, $naslov, TipKolone::Akcija);
    }

    /**
     * SQL izraz (kolona ili računski izraz) po kom se pretražuje i sortira.
     */
    public function izraz(string $izraz): self
    {
        $this->izraz = $izraz;

        return $this;
    }

    /**
     * Tekstualna kolona: dodatni izrazi koji ulaze u pretragu (npr. naziv uz simbol).
     */
    public function iTrazi(string ...$izrazi): self
    {
        $this->dodatniIzrazi = array_values([...$this->dodatniIzrazi, ...$izrazi]);

        return $this;
    }

    /**
     * Broj decimala na koji se vrednost zaokružuje u prikazu; pretraga poredi prikazanu vrednost.
     */
    public function decimale(int $decimale): self
    {
        $this->decimale = max(0, min(10, $decimale));

        return $this;
    }

    /**
     * Enum opcija: vrednost, naziv koji se traži i SQL uslov koji je opisuje.
     * Redosled dodavanja je redosled sortiranja.
     *
     * @param  list<mixed>  $bindings
     */
    public function opcija(string $vrednost, string $naziv, string $uslov, array $bindings = []): self
    {
        $this->opcije[] = ['vrednost' => $vrednost, 'naziv' => $naziv, 'uslov' => $uslov, 'bindings' => $bindings];

        return $this;
    }

    public function bezSortiranja(): self
    {
        $this->sortabilna = false;

        return $this;
    }

    public function bezPretrage(): self
    {
        $this->pretraziva = false;

        return $this;
    }

    public function skrivenNaslov(): self
    {
        $this->skrivenNaslov = true;

        return $this;
    }

    public function klasa(string $klasa): self
    {
        $this->klasa = $klasa;

        return $this;
    }

    /**
     * @param  Closure(mixed): (string|Htmlable|null)  $prikaz
     */
    public function prikaz(Closure $prikaz): self
    {
        $this->prikaz = $prikaz;

        return $this;
    }

    public function jeSortabilna(): bool
    {
        return $this->sortabilna;
    }

    public function jePretraziva(): bool
    {
        return $this->pretraziva;
    }

    public function imaSkrivenNaslov(): bool
    {
        return $this->skrivenNaslov;
    }

    public function css(): string
    {
        return $this->klasa;
    }

    /**
     * Klasa zaglavlja: samo poravnanje brojeva, bez stila ćelija.
     */
    public function cssZaglavlja(): string
    {
        return in_array('broj', explode(' ', $this->klasa), true) ? 'broj' : '';
    }

    public function sqlIzraz(): string
    {
        return $this->izraz ?? $this->kljuc;
    }

    /**
     * Sadržaj ćelije za red: zadati prikaz ili podrazumevani format po tipu.
     */
    public function prikazi(mixed $red): string|Htmlable|null
    {
        if ($this->prikaz !== null) {
            return ($this->prikaz)($red);
        }

        $vrednost = data_get($red, $this->kljuc);

        return match (true) {
            $vrednost === null => null,
            $this->tip === TipKolone::Decimalni => Decimal::format(Decimal::n($vrednost), $this->decimale),
            $this->tip === TipKolone::Datum && $vrednost instanceof CarbonInterface => $vrednost->setTimezone($this->zona)->format('d.m.Y'),
            $vrednost instanceof \BackedEnum => (string) $vrednost->value,
            default => (string) $vrednost,
        };
    }

    /**
     * Uslovi koje tekst pretrage daje za ovu kolonu; među sobom se vezuju sa OR.
     *
     * @return list<array{0: string, 1: list<mixed>}>
     */
    public function usloviPretrage(string $tekst): array
    {
        if (! $this->pretraziva || $tekst === '') {
            return [];
        }

        $izraz = $this->sqlIzraz();

        return match ($this->tip) {
            TipKolone::Tekst => array_map(
                fn (string $i) => ["{$i} LIKE ? ESCAPE '!'", ['%'.self::escapeLike($tekst).'%']],
                [$izraz, ...$this->dodatniIzrazi],
            ),
            TipKolone::Ceo => ($broj = TumacPretrage::ceoBroj($tekst)) === null ? [] : [["{$izraz} = ?", [$broj]]],
            TipKolone::Decimalni => $this->usloviDecimalni($izraz, $tekst),
            TipKolone::Datum => ($opseg = TumacPretrage::opsegDatuma($tekst, $this->zona)) === null
                ? []
                : [["({$izraz} >= ? AND {$izraz} < ?)", $opseg]],
            TipKolone::Enum => $this->usloviEnum($tekst),
            TipKolone::Akcija => [],
        };
    }

    /**
     * ORDER BY za smer: NULL vrednosti (npr. "nema kursa") uvek idu na kraj.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public function sortiranje(string $smer): array
    {
        $smer = $smer === 'desc' ? 'DESC' : 'ASC';

        if ($this->tip === TipKolone::Enum && $this->opcije !== []) {
            $grane = '';
            $bindings = [];

            foreach ($this->opcije as $rang => $opcija) {
                $grane .= " WHEN {$opcija['uslov']} THEN {$rang}";
                array_push($bindings, ...$opcija['bindings']);
            }

            return ["CASE{$grane} ELSE ".count($this->opcije)." END {$smer}", $bindings];
        }

        $izraz = $this->sqlIzraz();

        return ["CASE WHEN ({$izraz}) IS NULL THEN 1 ELSE 0 END, {$izraz} {$smer}", []];
    }

    /**
     * Prikazana vrednost "počinje" unetim brojem: 1.519 pogađa 1.519,00–1.519,99,
     * a 1.519,9 pogađa 1.519,90–1.519,99 (poređenje zaokruženo na broj decimala prikaza).
     *
     * @return list<array{0: string, 1: list<mixed>}>
     */
    private function usloviDecimalni(string $izraz, string $tekst): array
    {
        $uslovi = [];

        foreach (TumacPretrage::brojevi($tekst) as [$broj, $decimale]) {
            if ($decimale > 10) {
                continue;
            }

            $preciznost = max($this->decimale, $decimale);
            $korak = new Number($decimale === 0 ? '1' : '0.'.str_repeat('0', $decimale - 1).'1');
            $zaokruzeno = "ROUND({$izraz}, {$preciznost})";
            $decimal = 'CAST(? AS DECIMAL(38,10))';

            $uslovi[] = str_starts_with((string) $broj, '-')
                ? ["({$zaokruzeno} > {$decimal} AND {$zaokruzeno} <= {$decimal})", [(string) ($broj - $korak), (string) $broj]]
                : ["({$zaokruzeno} >= {$decimal} AND {$zaokruzeno} < {$decimal})", [(string) $broj, (string) ($broj + $korak)]];
        }

        return $uslovi;
    }

    /**
     * @return list<array{0: string, 1: list<mixed>}>
     */
    private function usloviEnum(string $tekst): array
    {
        $tekst = mb_strtolower($tekst);

        return array_values(array_map(
            fn (array $o) => ["({$o['uslov']})", $o['bindings']],
            array_filter(
                $this->opcije,
                fn (array $o) => str_contains(mb_strtolower($o['naziv']), $tekst) || mb_strtolower($o['vrednost']) === $tekst,
            ),
        ));
    }

    /**
     * LIKE sa "!" kao escape znakom radi isto u MySQL-u i SQLite-u.
     */
    private static function escapeLike(string $tekst): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $tekst);
    }
}
