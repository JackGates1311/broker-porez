<?php

namespace App\Services\Porezi;

use App\Enums\TipTransakcije;
use App\Models\Korisnik;
use App\Models\Transakcija;
use App\Services\Porezi\Dto\FifoTransakcija;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * Ponovo gradi poreske lotove i alokacije prodaja za korisnika iz njegovih transakcija.
 * Idempotentno: pokreće se posle svakog uvoza i svake promene kurseva.
 */
class FifoService
{
    public function __construct(private readonly FifoKalkulator $kalkulator) {}

    public function preracunaj(Korisnik $korisnik): void
    {
        $transakcije = Transakcija::query()
            ->where('korisnik_id', $korisnik->id)
            ->whereNotNull('imovina_id')
            ->tipa(TipTransakcije::Kupovina, TipTransakcije::Prodaja)
            ->orderBy('vreme_utc')
            ->orderBy('id')
            ->get()
            ->map(fn (Transakcija $t) => new FifoTransakcija(
                id: $t->id,
                imovinaId: $t->imovina_id,
                tip: $t->tip(),
                kolicina: $t->decimal('kolicina') ?? Decimal::nula(),
                cenaPoAkciji: $t->decimal('cena_po_akciji') ?? Decimal::nula(),
                kurs: $t->decimal('kurs'),
            ));

        $rezultat = $this->kalkulator->izracunaj($transakcije);

        DB::transaction(function () use ($korisnik, $rezultat) {
            DB::table('alokacije_lotova_prodaje')->where('korisnik_id', $korisnik->id)->delete();
            DB::table('poreski_lotovi')->where('korisnik_id', $korisnik->id)->delete();

            foreach (array_chunk($rezultat->lotovi, 500) as $deo) {
                DB::table('poreski_lotovi')->insert(array_map(fn ($lot) => [
                    'korisnik_id' => $korisnik->id,
                    'transakcija_kupovine_id' => $lot->transakcijaKupovineId,
                    'imovina_id' => $lot->imovinaId,
                    'pocetna_kolicina' => Decimal::zaBazu($lot->pocetnaKolicina),
                    'preostala_kolicina' => Decimal::zaBazu($lot->preostalaKolicina),
                    'nabavna_cena_po_jedinici' => Decimal::zaBazu($lot->nabavnaCenaPoJedinici),
                ], $deo));
            }

            $lotPoKupovini = DB::table('poreski_lotovi')
                ->where('korisnik_id', $korisnik->id)
                ->pluck('id', 'transakcija_kupovine_id');

            foreach (array_chunk($rezultat->alokacije, 500) as $deo) {
                DB::table('alokacije_lotova_prodaje')->insert(array_map(fn ($a) => [
                    'korisnik_id' => $korisnik->id,
                    'transakcija_prodaje_id' => $a->transakcijaProdajeId,
                    'poreski_lot_id' => $lotPoKupovini[$a->transakcijaKupovineId],
                    'iskoriscena_kolicina' => Decimal::zaBazu($a->kolicina),
                    'nabavna_vrednost_rsd' => Decimal::zaBazu($a->nabavnaVrednostRsd),
                ], $deo));
            }
        });
    }
}
