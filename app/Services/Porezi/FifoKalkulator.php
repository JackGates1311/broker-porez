<?php

namespace App\Services\Porezi;

use App\Enums\TipTransakcije;
use App\Services\Porezi\Dto\FifoAlokacija;
use App\Services\Porezi\Dto\FifoLot;
use App\Services\Porezi\Dto\FifoRezultat;
use App\Services\Porezi\Dto\FifoTransakcija;
use App\Support\Decimal;
use BcMath\Number;

/**
 * Port makroa IZRACUNAJ_PORESKU_OSNOVICU / PREOSTALO_IZ_LOT_A iz Excela.
 *
 * Makro za svaku prodaju prvo "potroši" ranije kupovine ranijim prodajama, pa ostatak
 * koristi za nabavnu vrednost tekuće prodaje. To je isto što i hronološko trošenje
 * lotova (FIFO): kupovina otvara lot, prodaja troši najstarije lotove iste imovine.
 * Nabavna vrednost uzete količine = količina × cena kupovine × NBS kurs na dan kupovine.
 */
final class FifoKalkulator
{
    /**
     * @param  iterable<FifoTransakcija>  $transakcije  hronološki sortirane (vreme, id)
     */
    public function izracunaj(iterable $transakcije): FifoRezultat
    {
        /** @var array<int|string, list<FifoLot>> $otvoreniPoImovini */
        $otvoreniPoImovini = [];
        $lotovi = [];
        $alokacije = [];
        $nedostajuce = [];
        $bezKursa = [];

        foreach ($transakcije as $transakcija) {
            if ($transakcija->tip === TipTransakcije::Kupovina) {
                $lot = new FifoLot(
                    transakcijaKupovineId: $transakcija->id,
                    imovinaId: $transakcija->imovinaId,
                    pocetnaKolicina: $transakcija->kolicina,
                    preostalaKolicina: $transakcija->kolicina,
                    nabavnaCenaPoJedinici: $transakcija->cenaPoAkciji,
                    kurs: $transakcija->kurs,
                );

                $lotovi[] = $lot;
                $otvoreniPoImovini[$transakcija->imovinaId][] = $lot;

                continue;
            }

            if ($transakcija->tip !== TipTransakcije::Prodaja) {
                continue;
            }

            $preostaloZaProdaju = $transakcija->kolicina;

            foreach ($otvoreniPoImovini[$transakcija->imovinaId] ?? [] as $lot) {
                if ($preostaloZaProdaju <= Decimal::nula()) {
                    break;
                }

                if ($lot->preostalaKolicina <= Decimal::nula()) {
                    continue;
                }

                $uzeto = Decimal::min($lot->preostalaKolicina, $preostaloZaProdaju);

                if ($lot->kurs === null) {
                    $bezKursa[$transakcija->id] = true;
                }

                $alokacije[] = new FifoAlokacija(
                    transakcijaProdajeId: $transakcija->id,
                    transakcijaKupovineId: $lot->transakcijaKupovineId,
                    kolicina: $uzeto,
                    nabavnaVrednostRsd: $uzeto * $lot->nabavnaCenaPoJedinici * ($lot->kurs ?? Decimal::nula()),
                );

                $lot->preostalaKolicina -= $uzeto;
                $preostaloZaProdaju -= $uzeto;
            }

            if ($preostaloZaProdaju > Decimal::nula()) {
                $nedostajuce[$transakcija->id] = $preostaloZaProdaju;
            }
        }

        return new FifoRezultat($lotovi, $alokacije, $nedostajuce, array_keys($bezKursa));
    }

    /**
     * Ukupna nabavna vrednost (RSD) po prodaji: kolona "Poreska osnovica" iz Excela.
     *
     * @return array<int, Number>
     */
    public static function nabavnaPoProdaji(FifoRezultat $rezultat): array
    {
        $zbir = [];

        foreach ($rezultat->alokacije as $alokacija) {
            $zbir[$alokacija->transakcijaProdajeId] = ($zbir[$alokacija->transakcijaProdajeId] ?? Decimal::nula())
                + $alokacija->nabavnaVrednostRsd;
        }

        return $zbir;
    }
}
