<?php

namespace App\Services;

use App\Models\OcenaZadovoljstva;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Statistika ocena zadovoljstva za kalendarski mesec (Europe/Belgrade), uz poređenje sa prethodnim mesecom.
 */
class ZadovoljstvoIzvestaj
{
    public const ZONA = 'Europe/Belgrade';

    private const MESECI = [1 => 'januar', 'februar', 'mart', 'april', 'maj', 'jun', 'jul', 'avgust', 'septembar', 'oktobar', 'novembar', 'decembar'];

    /**
     * Mesec za naslov izveštaja, npr. „oktobar 2026. godine” (Zadovoljstvo insajdera za …).
     */
    public static function nazivMeseca(CarbonImmutable $mesec): string
    {
        return self::MESECI[$mesec->month].' '.$mesec->year.'. godine';
    }

    /**
     * Prvi dan meseca (Europe/Belgrade) iz opcije komande: prosli-mesec, ovaj-mesec ili 2026-10.
     */
    public static function mesec(string $period, ?CarbonImmutable $sada = null): CarbonImmutable
    {
        $sada = ($sada ?? CarbonImmutable::now())->setTimezone(self::ZONA);

        return match (true) {
            $period === 'prosli-mesec' => $sada->startOfMonth()->subMonth(),
            $period === 'ovaj-mesec' => $sada->startOfMonth(),
            preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1 => CarbonImmutable::createFromFormat('!Y-m', $period, self::ZONA),
            default => throw new InvalidArgumentException("Period „{$period}” nije ispravan. Upišite prosli-mesec, ovaj-mesec ili godinu i mesec (npr. 2026-10)."),
        };
    }

    /**
     * @return array{mesec: CarbonImmutable, sazetak: array<string, mixed>, prethodni: array<string, mixed>, komentari: list<array<string, mixed>>}
     */
    public function za(CarbonImmutable $mesec): array
    {
        $ocene = $this->ocene($mesec);

        return [
            'mesec' => $mesec,
            'sazetak' => self::sazetak($ocene),
            'prethodni' => self::sazetak($this->ocene($mesec->subMonth())),
            'komentari' => array_values(array_filter($ocene, fn (array $ocena) => filled($ocena['komentar']))),
        ];
    }

    /**
     * Broj odgovora, prosek, raspodela 1–5, procenat zadovoljnih (ocene 4 i 5) i isto po povodu.
     *
     * @param  list<array{ocena: int, povod: string}>  $ocene
     * @return array{broj: int, prosek: ?float, raspodela: array<int, int>, zadovoljnih: ?int, po_povodu: array<string, array{broj: int, prosek: float}>}
     */
    public static function sazetak(array $ocene): array
    {
        $broj = count($ocene);
        $raspodela = array_fill_keys(range(1, 5), 0);
        $poPovodu = [];

        foreach ($ocene as $ocena) {
            $raspodela[$ocena['ocena']]++;
            $poPovodu[$ocena['povod']][] = $ocena['ocena'];
        }

        $povodi = [];

        foreach (array_keys(OcenaZadovoljstva::POVODI) as $povod) {
            if (isset($poPovodu[$povod])) {
                $povodi[$povod] = ['broj' => count($poPovodu[$povod]), 'prosek' => self::prosek($poPovodu[$povod])];
            }
        }

        return [
            'broj' => $broj,
            'prosek' => $broj > 0 ? self::prosek(array_column($ocene, 'ocena')) : null,
            'raspodela' => $raspodela,
            'zadovoljnih' => $broj > 0 ? (int) round(($raspodela[4] + $raspodela[5]) * 100 / $broj) : null,
            'po_povodu' => $povodi,
        ];
    }

    /**
     * @param  list<int>  $ocene
     */
    private static function prosek(array $ocene): float
    {
        return round(array_sum($ocene) / count($ocene), 2);
    }

    /**
     * @return list<array{ocena: int, povod: string, komentar: ?string, korisnicko_ime: ?string, kreirano_at: CarbonImmutable}>
     */
    private function ocene(CarbonImmutable $mesec): array
    {
        return OcenaZadovoljstva::query()
            ->with('korisnik:id,korisnicko_ime')
            ->where('kreirano_at', '>=', $mesec->utc())
            ->where('kreirano_at', '<', $mesec->addMonth()->utc())
            ->orderBy('kreirano_at')
            ->orderBy('id')
            ->get()
            ->map(fn (OcenaZadovoljstva $ocena) => [
                'ocena' => $ocena->ocena,
                'povod' => $ocena->povod,
                'komentar' => $ocena->komentar,
                'korisnicko_ime' => $ocena->korisnik?->korisnicko_ime,
                'kreirano_at' => CarbonImmutable::parse($ocena->kreirano_at)->setTimezone(self::ZONA),
            ])
            ->all();
    }
}
