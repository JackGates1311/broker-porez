<?php

namespace App\Services\Uvoz\Rucni;

/**
 * Čita sirove redove CSV-a po podešavanjima iz čarobnjaka (separator, kodna strana,
 * red zaglavlja). Ne tumači vrednosti; to radi RucniCsvParser.
 */
final class CsvCitac
{
    /**
     * Zaglavlje i redovi ispod njega. Ključ reda je broj linije u fajlu (od 1), za poruke o greškama.
     * Prazna i duplirana imena kolona dobijaju jedinstven naziv ("Kolona 3", "Iznos (2)").
     *
     * @return array{zaglavlje: list<string>, redovi: array<int, list<string>>}
     */
    public function procitaj(string $putanja, PodesavanjaUvoza $p, ?int $najvise = null): array
    {
        $zaglavlje = null;
        $redovi = [];

        foreach ($this->zapisi($putanja, $p) as $broj => [$linija, $celije]) {
            if ($broj < $p->redZaglavlja) {
                continue;
            }

            if ($zaglavlje === null) {
                $zaglavlje = $this->jedinstvenaImena($celije);

                continue;
            }

            $redovi[$linija] = $celije;

            if ($najvise !== null && count($redovi) >= $najvise) {
                break;
            }
        }

        return ['zaglavlje' => $zaglavlje ?? [], 'redovi' => $redovi];
    }

    /**
     * Različite neprazne vrednosti jedne kolone sa brojem pojavljivanja, najčešće prve.
     *
     * @return array<string, int>
     */
    public function vrednostiKolone(string $putanja, PodesavanjaUvoza $p, string $kolona): array
    {
        ['zaglavlje' => $zaglavlje, 'redovi' => $redovi] = $this->procitaj($putanja, $p);
        $indeks = array_search($kolona, $zaglavlje, true);

        if ($indeks === false) {
            return [];
        }

        $brojevi = [];

        foreach ($redovi as $celije) {
            $vrednost = trim($celije[$indeks] ?? '', " \"\t");

            if ($vrednost !== '') {
                $brojevi[$vrednost] = ($brojevi[$vrednost] ?? 0) + 1;
            }
        }

        arsort($brojevi);

        return $brojevi;
    }

    /**
     * Prvih $n zapisa od početka fajla (i pre zaglavlja), za pregled u koraku "Format".
     *
     * @return list<list<string>>
     */
    public function pocetak(string $putanja, PodesavanjaUvoza $p, int $n = 10): array
    {
        $zapisi = [];

        foreach ($this->zapisi($putanja, $p) as [, $celije]) {
            $zapisi[] = $celije;

            if (count($zapisi) >= $n) {
                break;
            }
        }

        return $zapisi;
    }

    /**
     * Separator koji se najčešće javlja (van navodnika) u prvih nekoliko linija.
     */
    public function detektujSeparator(string $putanja): string
    {
        $tekst = $this->ucitaj($putanja, 'UTF-8', 64 * 1024);
        $linije = array_slice(array_filter(preg_split('/\r\n|\n|\r/', $tekst) ?: [], fn ($l) => trim($l) !== ''), 0, 5);
        $najbolji = ',';
        $najvise = 0;

        foreach (array_keys(PodesavanjaUvoza::SEPARATORI) as $separator) {
            $ukupno = 0;

            foreach ($linije as $linija) {
                $ukupno += substr_count((string) preg_replace('/"[^"]*"/', '', $linija), $separator);
            }

            if ($ukupno > $najvise) {
                $najbolji = $separator;
                $najvise = $ukupno;
            }
        }

        return $najbolji;
    }

    /**
     * Neprazni CSV zapisi: redni broj zapisa (od 1) => [broj linije u fajlu, ćelije].
     *
     * @return \Generator<int, array{0: int, 1: list<string>}>
     */
    private function zapisi(string $putanja, PodesavanjaUvoza $p): \Generator
    {
        $tok = fopen('php://temp', 'r+');
        fwrite($tok, $this->ucitaj($putanja, $p->kodnaStrana));
        rewind($tok);

        $broj = 0;
        $linija = 1;

        try {
            while (($celije = fgetcsv($tok, null, $p->separator, '"', '')) !== false) {
                $pocetak = $linija;
                // Ćelija pod navodnicima može da se prostire kroz više linija.
                $linija += 1 + array_sum(array_map(fn ($c) => substr_count((string) $c, "\n"), $celije));

                if ($celije === [null] || implode('', array_map('trim', array_map('strval', $celije))) === '') {
                    continue;
                }

                yield ++$broj => [$pocetak, array_map(fn ($c) => trim((string) $c), $celije)];
            }
        } finally {
            fclose($tok);
        }
    }

    private function ucitaj(string $putanja, string $kodnaStrana, ?int $najvise = null): string
    {
        $tekst = (string) file_get_contents($putanja, length: $najvise);
        $tekst = (string) preg_replace('/^\xEF\xBB\xBF/', '', $tekst);

        if ($kodnaStrana !== 'UTF-8') {
            $tekst = (string) iconv($kodnaStrana, 'UTF-8//IGNORE', $tekst);
        } elseif (! mb_check_encoding($tekst, 'UTF-8')) {
            // Pogrešno izabrana kodna strana ne sme da obori stranicu; nevažeći bajtovi postaju "?".
            $tekst = mb_scrub($tekst, 'UTF-8');
        }

        return $tekst;
    }

    /**
     * @param  list<string>  $imena
     * @return list<string>
     */
    private function jedinstvenaImena(array $imena): array
    {
        $vidjeno = [];

        foreach ($imena as $i => $ime) {
            $ime = $ime === '' ? 'Kolona '.($i + 1) : $ime;
            $osnova = $ime;

            for ($n = 2; isset($vidjeno[$ime]); $n++) {
                $ime = "{$osnova} ({$n})";
            }

            $vidjeno[$ime] = true;
            $imena[$i] = $ime;
        }

        return $imena;
    }
}
