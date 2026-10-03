<?php

namespace App\Services\Kursevi;

use App\Models\Korisnik;
use App\Models\Transakcija;
use App\Services\Porezi\FifoService;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Builder;

/**
 * Upisuje NBS kurs u transakcije kojima nedostaje i ponovo pokreće FIFO obračun.
 */
class PrimenaKurseva
{
    public function __construct(
        private readonly NbsKursService $kursevi,
        private readonly FifoService $fifo,
    ) {}

    /**
     * Preuzima kurseve koji nedostaju (API), upisuje ih i preračunava FIFO.
     *
     * @return list<string> datumi za koje kurs nije mogao da se preuzme
     */
    public function preuzmiIPrimeni(Korisnik $korisnik): array
    {
        $parovi = [];

        foreach ($this->bezKursa($korisnik)->get() as $t) {
            $datum = $t->vremeSrb()->format('Y-m-d');

            if ($t->kurs === null && $t->valuta_cene !== null) {
                $parovi[] = [$datum, $t->valuta_cene];
            }

            if ($t->kurs_porez === null && $t->valuta_poreza !== null) {
                $parovi[] = [$datum, $t->valuta_poreza];
            }
        }

        $neuspesni = $this->kursevi->obezbediKurseve($parovi);
        $this->primeni($korisnik);

        return $neuspesni;
    }

    public function primeni(Korisnik $korisnik): void
    {
        foreach ($this->bezKursa($korisnik)->get() as $t) {
            $datum = $t->vremeSrb()->format('Y-m-d');
            $izmene = [];

            if ($t->kurs === null && $t->valuta_cene !== null) {
                $izmene['kurs'] = Decimal::zaBazu($this->kursevi->kurs($t->valuta_cene, $datum));
            }

            if ($t->kurs_porez === null && $t->valuta_poreza !== null) {
                $izmene['kurs_porez'] = Decimal::zaBazu($this->kursevi->kurs($t->valuta_poreza, $datum));
            }

            $izmene = array_filter($izmene, fn ($v) => $v !== null);

            if ($izmene !== []) {
                $t->update($izmene);
            }
        }

        $this->fifo->preracunaj($korisnik);
    }

    /**
     * Transakcije koje zahtevaju kurs (imaju valutu cene ili poreza), a nemaju ga.
     *
     * @return Builder<Transakcija>
     */
    public function bezKursa(Korisnik $korisnik): Builder
    {
        return Transakcija::query()
            ->where('korisnik_id', $korisnik->id)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNull('kurs')->whereNotNull('valuta_cene'))
                ->orWhere(fn ($q) => $q->whereNull('kurs_porez')->whereNotNull('valuta_poreza')));
    }
}
