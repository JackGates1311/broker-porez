<?php

namespace App\Support\Tabela;

use Illuminate\Support\Arr;

/**
 * Pretraga i sortiranje iz URL-a: ?q=tekst&sort=vreme:desc,simbol:asc&vise=1.
 * "vise=1" uključuje režim u kom klik na zaglavlje dodaje kolonu u višestruki sort.
 * Neispravne vrednosti se tiho odbacuju, da stari ili ručno izmenjeni linkovi ne pucaju.
 */
final readonly class StanjeTabele
{
    public const MAKS_PRETRAGA = 100;

    public const MAKS_SORTOVA = 5;

    /**
     * @param  list<array{0: string, 1: 'asc'|'desc'}>  $sort  korisnički sort, redom po prioritetu
     * @param  array<string, mixed>  $upit  svi query parametri trenutnog zahteva
     */
    public function __construct(
        public string $pretraga,
        public array $sort,
        public bool $viseKolona,
        private string $adresa,
        private array $upit,
    ) {}

    /**
     * @param  array<string, mixed>  $upit
     * @param  list<string>  $dozvoljeniSort  ključevi kolona po kojima sme da se sortira
     */
    public static function izUpita(array $upit, array $dozvoljeniSort, string $adresa): self
    {
        $pretraga = is_string($upit['q'] ?? null) ? trim($upit['q']) : '';
        $pretraga = mb_substr(preg_replace('/\s+/u', ' ', $pretraga) ?? '', 0, self::MAKS_PRETRAGA);

        $sort = [];
        $tekstSorta = is_string($upit['sort'] ?? null) ? mb_substr($upit['sort'], 0, 200) : '';

        foreach (array_filter(explode(',', $tekstSorta)) as $deo) {
            [$kljuc, $smer] = array_pad(explode(':', trim($deo), 2), 2, 'asc');

            if (! in_array($kljuc, $dozvoljeniSort, true) || ! in_array($smer, ['asc', 'desc'], true)) {
                continue;
            }

            if (in_array($kljuc, array_column($sort, 0), true)) {
                continue;
            }

            $sort[] = [$kljuc, $smer];

            if (count($sort) === self::MAKS_SORTOVA) {
                break;
            }
        }

        return new self($pretraga, $sort, ($upit['vise'] ?? null) === '1', $adresa, $upit);
    }

    public function jeIzmenjeno(): bool
    {
        return $this->pretraga !== '' || $this->sort !== [] || $this->viseKolona;
    }

    /**
     * Sort koji se zaista primenjuje: korisnički, ili podrazumevani ako ga nema.
     *
     * @param  list<array{0: string, 1: 'asc'|'desc'}>  $podrazumevani
     * @return list<array{0: string, 1: 'asc'|'desc'}>
     */
    public function efektivniSort(array $podrazumevani): array
    {
        return $this->sort !== [] ? $this->sort : $podrazumevani;
    }

    /**
     * Link za klik na zaglavlje.
     *
     * Običan klik: sortira samo po toj koloni; ponovni klik obrće smer.
     * Sa $dodaj (režim više kolona): dodaje kolonu na kraj višestrukog sorta; ako je već tu,
     * obrće smer rastuće → opadajuće, pa je uklanja.
     *
     * @param  list<array{0: string, 1: 'asc'|'desc'}>  $podrazumevani
     */
    public function urlSortiranja(string $kljuc, bool $dodaj, array $podrazumevani): string
    {
        $trenutni = $this->efektivniSort($podrazumevani);

        if (! $dodaj) {
            $novi = count($trenutni) === 1 && $trenutni[0][0] === $kljuc
                ? [[$kljuc, $trenutni[0][1] === 'asc' ? 'desc' : 'asc']]
                : [[$kljuc, 'asc']];

            return $this->url($novi);
        }

        $pozicija = array_search($kljuc, array_column($trenutni, 0), true);

        if ($pozicija === false) {
            $novi = count($trenutni) < self::MAKS_SORTOVA ? [...$trenutni, [$kljuc, 'asc']] : $trenutni;
        } elseif ($trenutni[$pozicija][1] === 'asc') {
            $novi = $trenutni;
            $novi[$pozicija] = [$kljuc, 'desc'];
        } else {
            $novi = array_values(array_filter($trenutni, fn ($s) => $s[0] !== $kljuc));
        }

        return $this->url($novi);
    }

    /**
     * Link koji uključuje ili isključuje režim sortiranja po više kolona; sort ostaje.
     */
    public function urlViseKolona(): string
    {
        $upit = Arr::except($this->upit, ['vise', 'page']);

        if (! $this->viseKolona) {
            $upit['vise'] = '1';
        }

        return $this->sastavi($upit);
    }

    /**
     * Link koji uklanja jednu kolonu iz korisničkog sorta.
     */
    public function urlBezSorta(string $kljuc): string
    {
        return $this->url(array_values(array_filter($this->sort, fn ($s) => $s[0] !== $kljuc)));
    }

    /**
     * Fabrička podešavanja: bez pretrage, korisničkog sorta i režima više kolona;
     * ostali parametri (npr. period) ostaju.
     */
    public function urlReseta(): string
    {
        return $this->sastavi(Arr::except($this->upit, ['q', 'sort', 'vise', 'page']));
    }

    /**
     * Pozicija kolone u višestrukom sortu (1, 2, …) i smer, ili null.
     *
     * @param  list<array{0: string, 1: 'asc'|'desc'}>  $podrazumevani
     * @return array{0: int, 1: 'asc'|'desc'}|null
     */
    public function prioritet(string $kljuc, array $podrazumevani): ?array
    {
        foreach ($this->efektivniSort($podrazumevani) as $i => [$k, $smer]) {
            if ($k === $kljuc) {
                return [$i + 1, $smer];
            }
        }

        return null;
    }

    /**
     * Parametri koje forma pretrage šalje kao skrivena polja (sve osim q i strane).
     *
     * @return array<string, string>
     */
    public function skrivenaPolja(): array
    {
        return array_filter(
            Arr::except($this->upit, ['q', 'page']),
            fn ($v) => is_string($v) && $v !== '',
        );
    }

    /**
     * @param  list<array{0: string, 1: 'asc'|'desc'}>  $sort
     */
    private function url(array $sort): string
    {
        $upit = Arr::except($this->upit, ['sort', 'page']);

        if ($sort !== []) {
            $upit['sort'] = implode(',', array_map(fn ($s) => "{$s[0]}:{$s[1]}", $sort));
        }

        return $this->sastavi($upit);
    }

    /**
     * @param  array<string, mixed>  $upit
     */
    private function sastavi(array $upit): string
    {
        $upit = array_filter($upit, fn ($v) => $v !== null && $v !== '');

        return $upit === [] ? $this->adresa : $this->adresa.'?'.Arr::query($upit);
    }
}
