<?php

namespace App\Support\Tabela;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Tabela sa globalnom pretragom (OR po svim kolonama) i sortiranjem po jednoj ili više
 * kolona. Sve se radi u SQL-u: primeni() dodaje WHERE i ORDER BY na upit stranice.
 * Prikaz: <x-tabela :tabela="$tabela" :redovi="$redovi" />.
 */
final class Tabela
{
    /** @var array<string, Kolona> */
    private array $kolone = [];

    /** @var list<array{0: string, 1: 'asc'|'desc'}> */
    private array $podrazumevaniSort = [];

    private ?string $tiebreak = null;

    private ?Closure $klasaReda = null;

    private StanjeTabele $stanje;

    /**
     * @param  list<Kolona>  $kolone
     */
    public function __construct(array $kolone)
    {
        foreach ($kolone as $kolona) {
            $this->kolone[$kolona->kljuc] = $kolona;
        }

        $this->stanje = StanjeTabele::izUpita([], [], '');
    }

    /**
     * @param  list<Kolona>  $kolone
     */
    public static function od(array $kolone): self
    {
        return new self($kolone);
    }

    /**
     * @param  'asc'|'desc'  $smer
     */
    public function podrazumevano(string $kljuc, string $smer = 'asc'): self
    {
        $this->podrazumevaniSort[] = [$kljuc, $smer];

        return $this;
    }

    /**
     * Jedinstvena kolona koja se uvek dodaje na kraj ORDER BY, da redosled bude stabilan.
     */
    public function tiebreak(string $izraz): self
    {
        $this->tiebreak = $izraz;

        return $this;
    }

    /**
     * @param  Closure(mixed): (string|array<array-key, mixed>)  $klasa
     */
    public function klasaReda(Closure $klasa): self
    {
        $this->klasaReda = $klasa;

        return $this;
    }

    public function izZahteva(Request $zahtev): self
    {
        return $this->izUpita($zahtev->query(), $zahtev->url());
    }

    /**
     * @param  array<string, mixed>  $upit
     */
    public function izUpita(array $upit, string $adresa = ''): self
    {
        $sortabilne = array_keys(array_filter($this->kolone, fn (Kolona $k) => $k->jeSortabilna()));
        $this->stanje = StanjeTabele::izUpita($upit, $sortabilne, $adresa);

        return $this;
    }

    /**
     * @template T of EloquentBuilder|QueryBuilder
     *
     * @param  T  $upit
     * @return T
     */
    public function primeni(EloquentBuilder|QueryBuilder $upit): EloquentBuilder|QueryBuilder
    {
        return $this->sortiraj($this->pretrazi($upit));
    }

    /**
     * @template T of EloquentBuilder|QueryBuilder
     *
     * @param  T  $upit
     * @return T
     */
    public function pretrazi(EloquentBuilder|QueryBuilder $upit): EloquentBuilder|QueryBuilder
    {
        if ($this->stanje->pretraga === '') {
            return $upit;
        }

        $uslovi = [];

        foreach ($this->kolone as $kolona) {
            array_push($uslovi, ...$kolona->usloviPretrage($this->stanje->pretraga));
        }

        if ($uslovi === []) {
            // Tekst ne odgovara nijednom tipu kolone (npr. slova u tabeli samo sa brojevima).
            return $upit->whereRaw('1 = 0');
        }

        return $upit->where(function ($q) use ($uslovi) {
            foreach ($uslovi as [$sql, $bindings]) {
                $q->orWhereRaw($sql, $bindings);
            }
        });
    }

    /**
     * @template T of EloquentBuilder|QueryBuilder
     *
     * @param  T  $upit
     * @return T
     */
    public function sortiraj(EloquentBuilder|QueryBuilder $upit): EloquentBuilder|QueryBuilder
    {
        $sort = $this->efektivniSort();

        foreach ($sort as [$kljuc, $smer]) {
            [$sql, $bindings] = $this->kolone[$kljuc]->sortiranje($smer);
            $upit->orderByRaw($sql, $bindings);
        }

        if ($this->tiebreak !== null) {
            $upit->orderBy($this->tiebreak, $sort[0][1] ?? 'asc');
        }

        return $upit;
    }

    /**
     * Za redove koji su već izračunati u PHP-u (npr. BcMath obračun nad celim periodom):
     * SQL vraća samo ID-jeve koji prolaze pretragu, redosledom sortiranja, a redovi se
     * uzimaju iz $redovi po tim ID-jevima.
     *
     * @template TRed
     *
     * @param  Collection<array-key, TRed>  $redovi  ključ je vrednost kolone $idKolona
     * @return Collection<int, TRed>
     */
    public function izaberi(EloquentBuilder|QueryBuilder $upit, string $idKolona, Collection $redovi): Collection
    {
        return $this->primeni($upit)
            ->pluck($idKolona)
            ->map(fn ($id) => $redovi->get($id))
            ->filter()
            ->values();
    }

    /**
     * @return array<string, Kolona>
     */
    public function kolone(): array
    {
        return $this->kolone;
    }

    public function stanje(): StanjeTabele
    {
        return $this->stanje;
    }

    /**
     * @return list<array{0: string, 1: 'asc'|'desc'}>
     */
    public function efektivniSort(): array
    {
        return array_values(array_filter(
            $this->stanje->efektivniSort($this->podrazumevaniSort),
            fn ($s) => isset($this->kolone[$s[0]]),
        ));
    }

    public function kolona(string $kljuc): ?Kolona
    {
        return $this->kolone[$kljuc] ?? null;
    }

    /**
     * Link zaglavlja; bez $dodaj važi režim iz URL-a (vise=1 dodaje kolonu u sort).
     */
    public function urlSortiranja(string $kljuc, ?bool $dodaj = null): string
    {
        return $this->stanje->urlSortiranja($kljuc, $dodaj ?? $this->stanje->viseKolona, $this->podrazumevaniSort);
    }

    /**
     * @return array{0: int, 1: 'asc'|'desc'}|null
     */
    public function prioritet(string $kljuc): ?array
    {
        return $this->stanje->prioritet($kljuc, $this->podrazumevaniSort);
    }

    /**
     * @return string|array<array-key, mixed>
     */
    public function klasaZa(mixed $red): string|array
    {
        return $this->klasaReda === null ? '' : ($this->klasaReda)($red);
    }
}
