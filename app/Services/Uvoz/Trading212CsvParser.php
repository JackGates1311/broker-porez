<?php

namespace App\Services\Uvoz;

use App\Enums\TipTransakcije;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use SplFileObject;
use Throwable;

/**
 * Čita Trading 212 "History" CSV export. Kolone se mapiraju po nazivu jer se skup
 * kolona razlikuje od exporta do exporta (Time / Time (UTC), opcione kolone za
 * ID, Notes, Result, porez po odbitku i razne naknade).
 */
final class Trading212CsvParser
{
    /**
     * Kolone sa naknadama koje se sabiraju u "provizija" (Charge amount NIJE naknada,
     * već iznos naplaćen sa kartice pri depozitu).
     */
    private const NAKNADE = [
        'Deposit fee',
        'Currency conversion fee',
        'French transaction tax',
        'Stamp duty reserve tax',
        'Transaction fee',
        'Finra fee',
    ];

    /** @var list<string> */
    private array $greske = [];

    /**
     * @return list<UvezeniRed>
     */
    public function parsiraj(string $putanja, string $nazivFajla = ''): array
    {
        $this->greske = [];
        $nazivFajla = $nazivFajla !== '' ? $nazivFajla : basename($putanja);

        $fajl = new SplFileObject($putanja, 'r');
        $fajl->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $fajl->setCsvControl(',', '"', '');

        $zaglavlje = null;
        $redovi = [];

        foreach ($fajl as $broj => $celije) {
            if (! is_array($celije) || $celije === [null]) {
                continue;
            }

            if ($zaglavlje === null) {
                $zaglavlje = array_map(fn ($k) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $k)), $celije);

                if (! in_array('Action', $zaglavlje, true) || array_intersect(['Time', 'Time (UTC)'], $zaglavlje) === []) {
                    throw new \InvalidArgumentException("Fajl {$nazivFajla} nije Trading 212 CSV export (nedostaju kolone Action i Time).");
                }

                continue;
            }

            $linija = $broj + 1;

            if (count($celije) !== count($zaglavlje)) {
                $this->greske[] = "{$nazivFajla}, red {$linija}: broj kolona se ne poklapa sa zaglavljem.";

                continue;
            }

            try {
                $redovi[] = $this->red(array_combine($zaglavlje, array_map('trim', $celije)));
            } catch (Throwable $e) {
                $this->greske[] = "{$nazivFajla}, red {$linija}: {$e->getMessage()}";
            }
        }

        return $redovi;
    }

    /**
     * @return list<string>
     */
    public function greske(): array
    {
        return $this->greske;
    }

    /**
     * @param  array<string, string>  $k
     */
    private function red(array $k): UvezeniRed
    {
        $akcija = $k['Action'];
        $vreme = CarbonImmutable::parse($k['Time (UTC)'] ?? $k['Time'], 'UTC')->utc();

        $kolicina = Decimal::izTeksta($k['No. of shares'] ?? null);
        $cena = Decimal::izTeksta($k['Price / share'] ?? null);
        $ukupno = Decimal::izTeksta($k['Total'] ?? null);
        $porez = Decimal::izTeksta($k['Withholding tax'] ?? null);
        $isin = $this->tekst($k['ISIN'] ?? null);

        $provizija = null;
        $valutaProvizije = null;

        foreach (self::NAKNADE as $kolona) {
            $iznos = Decimal::izTeksta($k[$kolona] ?? null);

            if ($iznos === null) {
                continue;
            }

            $provizija = ($provizija ?? Decimal::nula()) + ($iznos < Decimal::nula() ? -$iznos : $iznos);
            $valutaProvizije ??= $this->tekst($k["Currency ({$kolona})"] ?? null);
        }

        $kljuc = hash('sha256', implode('|', [
            strtolower($akcija),
            $vreme->format('Y-m-d H:i:s'),
            $isin ?? '',
            Decimal::kanonski($kolicina),
            Decimal::kanonski($cena),
            $isin === null ? Decimal::kanonski($ukupno) : '',
        ]));

        return new UvezeniRed(
            akcija: $akcija,
            tip: TipTransakcije::izAkcije($akcija),
            vremeUtc: $vreme,
            isin: $isin,
            simbol: $this->tekst($k['Ticker'] ?? null),
            naziv: $this->tekst($k['Name'] ?? null),
            brokerId: $this->tekst($k['ID'] ?? null),
            napomena: $this->tekst($k['Notes'] ?? null),
            kolicina: $kolicina,
            cenaPoAkciji: $cena,
            valutaCene: $this->tekst($k['Currency (Price / share)'] ?? null),
            ukupno: $ukupno ?? Decimal::nula(),
            valutaUkupno: $this->tekst($k['Currency (Total)'] ?? null) ?? '',
            porezPoOdbitku: $porez,
            valutaPoreza: $this->tekst($k['Currency (Withholding tax)'] ?? null),
            provizija: $provizija,
            valutaProvizije: $valutaProvizije,
            jedinstveniKljuc: $kljuc,
        );
    }

    private function tekst(?string $vrednost): ?string
    {
        $vrednost = trim((string) $vrednost, " \"\t");

        return $vrednost === '' ? null : $vrednost;
    }
}
