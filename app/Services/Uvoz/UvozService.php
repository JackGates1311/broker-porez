<?php

namespace App\Services\Uvoz;

use App\Models\Imovina;
use App\Models\Korisnik;
use App\Services\Kursevi\PrimenaKurseva;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * Upisuje izvod bilo kog brokera: parser daje redove nezavisne od brokera, a ovde
 * se radi dedupe, upis imovine i transakcija i preuzimanje kurseva (posle čega
 * PrimenaKurseva preračunava FIFO).
 */
class UvozService
{
    public function __construct(
        private readonly PrimenaKurseva $primenaKurseva,
    ) {}

    /**
     * @param  array<string, string>  $fajlovi  putanja => originalni naziv fajla
     */
    public function uvezi(Korisnik $korisnik, ParserIzvoda $parser, array $fajlovi): UvozRezime
    {
        $redovi = [];
        $greske = [];

        foreach ($fajlovi as $putanja => $naziv) {
            try {
                foreach ($parser->parsiraj($putanja, $naziv) as $red) {
                    // Isti red se može pojaviti u više fajlova (preklapajući periodi).
                    $redovi[$red->jedinstveniKljuc] = $red;
                }
            } catch (\InvalidArgumentException $e) {
                $greske[] = $e->getMessage();
            }

            array_push($greske, ...$parser->greske());
        }

        $uvezeno = DB::transaction(function () use ($korisnik, $redovi) {
            $imovinaPoIsinu = $this->sacuvajImovinu($redovi);
            $uvezeno = 0;

            foreach (array_chunk(array_values($redovi), 500) as $deo) {
                $uvezeno += DB::table('transakcije')->insertOrIgnore(array_map(fn (UvezeniRed $r) => [
                    'korisnik_id' => $korisnik->id,
                    'broker_transakcija_id' => $r->brokerId,
                    'imovina_id' => $r->isin !== null ? $imovinaPoIsinu[$r->isin] : null,
                    'tip_akcije' => mb_substr($r->akcija, 0, 50),
                    'tip' => $r->tip->value,
                    'izvor' => $r->izvor->value,
                    'vreme_utc' => $r->vremeUtc->format('Y-m-d H:i:s'),
                    'kolicina' => Decimal::zaBazu($r->kolicina) ?? '0',
                    'cena_po_akciji' => Decimal::zaBazu($r->cenaPoAkciji) ?? '0',
                    'valuta_cene' => $r->valutaCene,
                    'kurs' => null,
                    'ukupno' => Decimal::zaBazu($r->ukupno),
                    'valuta_ukupno' => $r->valutaUkupno,
                    'porez_po_odbitku' => Decimal::zaBazu($r->porezPoOdbitku) ?? '0',
                    'valuta_poreza' => $r->valutaPoreza,
                    'provizija' => Decimal::zaBazu($r->provizija) ?? '0',
                    'valuta_provizije' => $r->valutaProvizije,
                    'napomena' => $r->napomena,
                    'jedinstveni_kljuc' => $r->jedinstveniKljuc,
                ], $deo));
            }

            return $uvezeno;
        });

        $neuspesniKursevi = $this->primenaKurseva->preuzmiIPrimeni($korisnik);

        return new UvozRezime(
            procitano: count($redovi),
            uvezeno: $uvezeno,
            duplikati: count($redovi) - $uvezeno,
            greske: $greske,
            datumiBezKursa: $neuspesniKursevi,
        );
    }

    /**
     * Upsert imovine po ISIN-u; simbol i naziv se osvežavaju na poslednje poznate.
     *
     * @param  array<string, UvezeniRed>  $redovi
     * @return array<string, int> ISIN => imovina.id
     */
    private function sacuvajImovinu(array $redovi): array
    {
        $poIsinu = [];

        foreach ($redovi as $red) {
            if ($red->isin === null) {
                continue;
            }

            $postojeci = $poIsinu[$red->isin] ?? null;

            if ($postojeci === null || $red->vremeUtc > $postojeci->vremeUtc) {
                $poIsinu[$red->isin] = $red;
            }
        }

        foreach ($poIsinu as $isin => $red) {
            Imovina::query()->updateOrCreate(
                ['isin' => $isin],
                array_filter(['simbol' => $red->simbol ?? $isin, 'naziv' => $red->naziv]),
            );
        }

        return Imovina::query()->whereIn('isin', array_keys($poIsinu))->pluck('id', 'isin')->all();
    }
}
