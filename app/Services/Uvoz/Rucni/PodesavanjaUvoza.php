<?php

namespace App\Services\Uvoz\Rucni;

use App\Enums\TipTransakcije;

/**
 * Sve što čarobnjak sazna o CSV-u jednog brokera: format fajla, mapiranje kolona i
 * mapiranje vrednosti akcija. Čuva se u sesiji tokom čarobnjaka i kao JSON u
 * sabloni_uvoza. Kolone se pamte po nazivu zaglavlja, pa šablon preživi promenu
 * redosleda kolona.
 */
final readonly class PodesavanjaUvoza
{
    /** @var array<string, string> vrednost => naziv u formi */
    public const SEPARATORI = [',' => 'Zarez ( , )', ';' => 'Tačka-zarez ( ; )', "\t" => 'Tab', '|' => 'Uspravna crta ( | )'];

    /** @var array<string, string> */
    public const KODNE_STRANE = ['UTF-8' => 'UTF-8', 'Windows-1250' => 'Windows-1250 (srednja Evropa)'];

    /** @var array<string, string> */
    public const DECIMALNI_SEPARATORI = ['.' => 'Tačka (1234.56)', ',' => 'Zarez (1234,56)'];

    /** @var array<string, string> */
    public const SEPARATORI_HILJADA = ['' => 'Bez separatora', '.' => 'Tačka (1.234)', ',' => 'Zarez (1,234)', ' ' => 'Razmak (1 234)', "'" => "Apostrof (1'234)"];

    /** @var array<string, string> PHP format => primer */
    public const FORMATI_DATUMA = [
        'Y-m-d H:i:s' => '2026-03-15 14:30:00',
        'Y-m-d H:i' => '2026-03-15 14:30',
        'Y-m-d' => '2026-03-15',
        'd.m.Y H:i:s' => '15.03.2026 14:30:00',
        'd.m.Y H:i' => '15.03.2026 14:30',
        'd.m.Y' => '15.03.2026',
        'm/d/Y H:i:s' => '03/15/2026 14:30:00',
        'm/d/Y' => '03/15/2026',
        'd/m/Y H:i:s' => '15/03/2026 14:30:00',
        'd/m/Y' => '15/03/2026',
        'Y-m-d, H:i:s' => '2026-03-15, 14:30:00',
        'iso' => 'ISO 8601 (2026-03-15T14:30:00+01:00)',
    ];

    /** @var array<string, string> */
    public const VREMENSKE_ZONE = [
        'UTC' => 'UTC',
        'Europe/Belgrade' => 'Beograd (CET/CEST)',
        'Europe/London' => 'London',
        'America/New_York' => 'Njujork (ET)',
    ];

    /**
     * Znakovi koje bi forma izgubila (TrimStrings briše razmak, tab u atributu nije pouzdan)
     * šalju se kao kodovi.
     *
     * @var array<string, string> znak => kod u formi
     */
    private const KODOVI_ZNAKOVA = ["\t" => 'tab', ' ' => 'razmak', '' => 'bez'];

    public static function kodZnaka(string $znak): string
    {
        return self::KODOVI_ZNAKOVA[$znak] ?? $znak;
    }

    public static function znakIzKoda(?string $kod): string
    {
        $znak = array_search((string) $kod, self::KODOVI_ZNAKOVA, true);

        return $znak === false ? (string) $kod : $znak;
    }

    /**
     * @param  array<string, array{kolona?: string, konstanta?: string}>  $kolone  PoljeUvoza value => izvor vrednosti
     * @param  array<string, string>  $akcije  sirova vrednost akcije => TipTransakcije value
     */
    public function __construct(
        public string $separator = ',',
        public string $kodnaStrana = 'UTF-8',
        public int $redZaglavlja = 1,
        public string $decimalniSeparator = '.',
        public string $separatorHiljada = '',
        public string $formatDatuma = 'Y-m-d H:i:s',
        public string $vremenskaZona = 'UTC',
        public array $kolone = [],
        public array $akcije = [],
    ) {}

    /**
     * Nepoznate ili nevažeće vrednosti (npr. iz starog šablona) zamenjuju se podrazumevanim.
     *
     * @param  array<string, mixed>  $niz
     */
    public static function izNiza(array $niz): self
    {
        $izbor = fn (string $kljuc, array $dozvoljeno, string $podrazumevano) => is_string($niz[$kljuc] ?? null) && array_key_exists($niz[$kljuc], $dozvoljeno)
            ? $niz[$kljuc]
            : $podrazumevano;

        $kolone = [];

        foreach ((array) ($niz['kolone'] ?? []) as $polje => $izvor) {
            if (PoljeUvoza::tryFrom((string) $polje) === null || ! is_array($izvor)) {
                continue;
            }

            if (is_string($izvor['kolona'] ?? null) && $izvor['kolona'] !== '') {
                $kolone[$polje] = ['kolona' => $izvor['kolona']];
            } elseif (is_string($izvor['konstanta'] ?? null) && $izvor['konstanta'] !== '') {
                $kolone[$polje] = ['konstanta' => $izvor['konstanta']];
            }
        }

        $akcije = [];

        foreach ((array) ($niz['akcije'] ?? []) as $vrednost => $tip) {
            if (TipTransakcije::tryFrom((string) $tip) !== null) {
                $akcije[(string) $vrednost] = (string) $tip;
            }
        }

        return new self(
            separator: $izbor('separator', self::SEPARATORI, ','),
            kodnaStrana: $izbor('kodna_strana', self::KODNE_STRANE, 'UTF-8'),
            redZaglavlja: max(1, min(100, (int) ($niz['red_zaglavlja'] ?? 1))),
            decimalniSeparator: $izbor('decimalni_separator', self::DECIMALNI_SEPARATORI, '.'),
            separatorHiljada: $izbor('separator_hiljada', self::SEPARATORI_HILJADA, ''),
            formatDatuma: $izbor('format_datuma', self::FORMATI_DATUMA, 'Y-m-d H:i:s'),
            vremenskaZona: $izbor('vremenska_zona', self::VREMENSKE_ZONE, 'UTC'),
            kolone: $kolone,
            akcije: $akcije,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function uNiz(): array
    {
        return [
            'separator' => $this->separator,
            'kodna_strana' => $this->kodnaStrana,
            'red_zaglavlja' => $this->redZaglavlja,
            'decimalni_separator' => $this->decimalniSeparator,
            'separator_hiljada' => $this->separatorHiljada,
            'format_datuma' => $this->formatDatuma,
            'vremenska_zona' => $this->vremenskaZona,
            'kolone' => $this->kolone,
            'akcije' => $this->akcije,
        ];
    }

    /**
     * Kopija sa izmenjenim ključevima (isti ključevi kao uNiz()).
     *
     * @param  array<string, mixed>  $izmene
     */
    public function sa(array $izmene): self
    {
        return self::izNiza(array_replace($this->uNiz(), $izmene));
    }

    public function kolonaZa(PoljeUvoza $polje): ?string
    {
        return $this->kolone[$polje->value]['kolona'] ?? null;
    }

    public function konstantaZa(PoljeUvoza $polje): ?string
    {
        return $this->kolone[$polje->value]['konstanta'] ?? null;
    }

    /**
     * Kolone zaglavlja koje mapiranje koristi, a fajl ih nema.
     *
     * @param  list<string>  $zaglavlje
     * @return list<string>
     */
    public function nedostajuceKolone(array $zaglavlje): array
    {
        $kolone = array_filter(array_map(fn (array $izvor) => $izvor['kolona'] ?? null, $this->kolone));

        return array_values(array_unique(array_diff($kolone, $zaglavlje)));
    }

    public function imaObaveznaPolja(): bool
    {
        return $this->kolonaZa(PoljeUvoza::Vreme) !== null && $this->kolonaZa(PoljeUvoza::Akcija) !== null;
    }
}
