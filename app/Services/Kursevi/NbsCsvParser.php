<?php

namespace App\Services\Kursevi;

use BcMath\Number;
use Carbon\CarbonImmutable;
use SplFileObject;

/**
 * Čita kursnu listu preuzetu sa sajta NBS (kl-*.csv) sa kolonama
 * "Датум формирања, Датум примене, Валута, Назив земље, Ознака, Важи за, Средњи курс".
 * Kurs se vezuje za "Датум примене", kao u Excelu.
 */
final class NbsCsvParser
{
    /**
     * @return list<array{datum: string, valuta: string, kurs: Number}>
     */
    public function parsiraj(string $putanja, string $nazivFajla = ''): array
    {
        $nazivFajla = $nazivFajla !== '' ? $nazivFajla : basename($putanja);

        $fajl = new SplFileObject($putanja, 'r');
        $fajl->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $fajl->setCsvControl(',', '"', '');

        $indeksi = null;
        $kursevi = [];

        foreach ($fajl as $celije) {
            if (! is_array($celije) || $celije === [null]) {
                continue;
            }

            $celije = array_map(fn ($c) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c)), $celije);

            if ($indeksi === null) {
                $indeksi = [
                    'datum' => array_search('Датум примене', $celije, true),
                    'oznaka' => array_search('Ознака', $celije, true),
                    'vazi_za' => array_search('Важи за', $celije, true),
                    'srednji' => array_search('Средњи курс', $celije, true),
                ];

                if (in_array(false, $indeksi, true)) {
                    throw new \InvalidArgumentException(
                        "Fajl {$nazivFajla} nije NBS kursna lista sa srednjim kursom (potrebne kolone: Датум примене, Ознака, Важи за, Средњи курс)."
                    );
                }

                continue;
            }

            $datum = CarbonImmutable::createFromFormat('!d.m.Y', $celije[$indeksi['datum']] ?? '');
            $srednji = $celije[$indeksi['srednji']] ?? '';
            $vaziZa = $celije[$indeksi['vazi_za']] ?? '';

            if ($datum === false || ! is_numeric($srednji) || ! ctype_digit($vaziZa) || (int) $vaziZa === 0) {
                continue;
            }

            $kursevi[] = [
                'datum' => $datum->format('Y-m-d'),
                'valuta' => strtoupper($celije[$indeksi['oznaka']]),
                'kurs' => (new Number($srednji))->div($vaziZa, 10),
            ];
        }

        if ($indeksi === null) {
            throw new \InvalidArgumentException("Fajl {$nazivFajla} je prazan.");
        }

        return $kursevi;
    }
}
