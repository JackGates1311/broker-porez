<?php

namespace App\Services\Uvoz\Rucni;

use App\Enums\TipTransakcije;
use App\Services\Uvoz\IzvorUvoza;
use App\Services\Uvoz\ParserIzvoda;
use App\Services\Uvoz\UvezeniRed;
use App\Support\Decimal;
use BcMath\Number;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * CSV proizvoljnog brokera, po mapiranju koje je korisnik napravio u čarobnjaku.
 * Iznosi se uzimaju po apsolutnoj vrednosti (brokeri različito beleže znak prodaje,
 * poreza i naknada); smer određuje tip akcije.
 */
final class RucniCsvParser implements ParserIzvoda
{
    /** @var list<string> */
    private array $greske = [];

    public function __construct(
        private readonly PodesavanjaUvoza $podesavanja,
        private readonly CsvCitac $citac = new CsvCitac,
    ) {}

    public function parsiraj(string $putanja, string $nazivFajla = ''): array
    {
        $this->greske = [];
        $nazivFajla = $nazivFajla !== '' ? $nazivFajla : basename($putanja);
        $p = $this->podesavanja;

        if (! $p->imaObaveznaPolja()) {
            throw new \InvalidArgumentException('Mapiranje nije potpuno: izaberite kolone za datum i akciju.');
        }

        ['zaglavlje' => $zaglavlje, 'redovi' => $zapisi] = $this->citac->procitaj($putanja, $p);
        $nedostaju = $p->nedostajuceKolone($zaglavlje);

        if ($nedostaju !== []) {
            throw new \InvalidArgumentException("Fajl {$nazivFajla} nema kolone: ".implode(', ', $nedostaju).'.');
        }

        $redovi = [];

        foreach ($zapisi as $linija => $celije) {
            if (count($celije) !== count($zaglavlje)) {
                $this->greske[] = "{$nazivFajla}, red {$linija}: broj kolona se ne poklapa sa zaglavljem.";

                continue;
            }

            try {
                $redovi[] = $this->red(array_combine($zaglavlje, $celije));
            } catch (Throwable $e) {
                $this->greske[] = "{$nazivFajla}, red {$linija}: {$e->getMessage()}";
            }
        }

        return $redovi;
    }

    public function greske(): array
    {
        return $this->greske;
    }

    /**
     * @param  array<string, string>  $k
     */
    private function red(array $k): UvezeniRed
    {
        $akcija = $this->tekst($k, PoljeUvoza::Akcija) ?? throw new \InvalidArgumentException('prazna akcija.');
        $tip = TipTransakcije::tryFrom($this->podesavanja->akcije[$akcija] ?? '')
            ?? throw new \InvalidArgumentException("akcija „{$akcija}” nije mapirana na tip.");
        $vreme = $this->vreme($this->tekst($k, PoljeUvoza::Vreme) ?? throw new \InvalidArgumentException('prazan datum.'));

        $isin = $this->tekst($k, PoljeUvoza::Isin);
        $isin = $isin === null ? null : strtoupper($isin);
        $kolicina = $this->broj($k, PoljeUvoza::Kolicina);
        $cena = $this->broj($k, PoljeUvoza::Cena);
        $ukupno = $this->broj($k, PoljeUvoza::Ukupno);
        $valutaCene = $this->valuta($k, PoljeUvoza::ValutaCene);
        $porez = $this->broj($k, PoljeUvoza::Porez);
        $provizija = $this->broj($k, PoljeUvoza::Provizija);

        $saHartijom = in_array($tip, [TipTransakcije::Kupovina, TipTransakcije::Prodaja, TipTransakcije::Dividenda], true);

        if ($saHartijom) {
            if ($isin === null) {
                throw new \InvalidArgumentException('nedostaje ISIN.');
            }

            if (! preg_match('/^[A-Z]{2}[A-Z0-9]{9}\d$/', $isin)) {
                throw new \InvalidArgumentException("neispravan ISIN „{$isin}”.");
            }

            if ($valutaCene === null) {
                throw new \InvalidArgumentException('nedostaje valuta cene.');
            }
        }

        if ($tip === TipTransakcije::Kupovina || $tip === TipTransakcije::Prodaja) {
            if ($kolicina === null || $kolicina == Decimal::nula()) {
                throw new \InvalidArgumentException('nedostaje količina.');
            }

            if ($cena === null) {
                throw new \InvalidArgumentException('nedostaje cena po akciji.');
            }
        }

        if ($tip === TipTransakcije::Dividenda && ($kolicina === null || $cena === null)) {
            // Obračun dividende ide preko količina × neto po akciji; iznos bez količine je "1 × iznos".
            if ($ukupno === null) {
                throw new \InvalidArgumentException('dividenda nema ni količinu i cenu ni ukupan iznos.');
            }

            $kolicina = new Number('1');
            $cena = $ukupno;
        }

        if ($tip === TipTransakcije::Depozit && $ukupno === null) {
            throw new \InvalidArgumentException('depozit nema iznos.');
        }

        if ($ukupno === null && $kolicina !== null && $cena !== null) {
            $ukupno = $kolicina * $cena;
        }

        $valutaUkupno = $this->valuta($k, PoljeUvoza::ValutaUkupno) ?? $valutaCene;

        if ($tip === TipTransakcije::Depozit && $valutaUkupno === null) {
            throw new \InvalidArgumentException('nedostaje valuta iznosa.');
        }

        $kljuc = hash('sha256', implode('|', [
            IzvorUvoza::Rucni->value,
            strtolower($akcija),
            $vreme->format('Y-m-d H:i:s'),
            $isin ?? '',
            Decimal::kanonski($kolicina),
            Decimal::kanonski($cena),
            Decimal::kanonski($ukupno),
            $this->tekst($k, PoljeUvoza::BrokerId) ?? '',
        ]));

        return new UvezeniRed(
            akcija: $akcija,
            tip: $tip,
            vremeUtc: $vreme,
            isin: $saHartijom ? $isin : null,
            simbol: $this->tekst($k, PoljeUvoza::Simbol),
            naziv: $this->tekst($k, PoljeUvoza::Naziv),
            brokerId: $this->tekst($k, PoljeUvoza::BrokerId),
            napomena: $this->tekst($k, PoljeUvoza::Napomena),
            kolicina: $kolicina,
            cenaPoAkciji: $cena,
            valutaCene: $saHartijom ? $valutaCene : null,
            ukupno: $ukupno ?? Decimal::nula(),
            valutaUkupno: $valutaUkupno ?? '',
            porezPoOdbitku: $porez,
            valutaPoreza: $porez === null ? null : ($this->valuta($k, PoljeUvoza::ValutaPoreza) ?? $valutaCene ?? $valutaUkupno),
            provizija: $provizija,
            valutaProvizije: $provizija === null ? null : ($this->valuta($k, PoljeUvoza::ValutaProvizije) ?? $valutaCene ?? $valutaUkupno),
            jedinstveniKljuc: $kljuc,
            izvor: IzvorUvoza::Rucni,
        );
    }

    /**
     * @param  array<string, string>  $k
     */
    private function tekst(array $k, PoljeUvoza $polje): ?string
    {
        $kolona = $this->podesavanja->kolonaZa($polje);
        $vrednost = $kolona !== null ? ($k[$kolona] ?? '') : ($this->podesavanja->konstantaZa($polje) ?? '');
        $vrednost = trim($vrednost, " \"\t");

        return $vrednost === '' ? null : $vrednost;
    }

    /**
     * @param  array<string, string>  $k
     */
    private function valuta(array $k, PoljeUvoza $polje): ?string
    {
        $valuta = $this->tekst($k, $polje);

        if ($valuta === null) {
            return null;
        }

        // GBX (penija) ostaje GBX: NbsKursService ga preračunava kao GBP/100.
        $valuta = strtoupper($valuta);

        if (! preg_match('/^[A-Z]{3}$/', $valuta)) {
            throw new \InvalidArgumentException("neispravna valuta „{$valuta}”.");
        }

        return $valuta;
    }

    /**
     * @param  array<string, string>  $k
     */
    private function broj(array $k, PoljeUvoza $polje): ?Number
    {
        $tekst = $this->tekst($k, $polje);

        if ($tekst === null) {
            return null;
        }

        $p = $this->podesavanja;
        $broj = str_replace(["\u{00A0}", "\u{202F}"], ' ', $tekst);

        if ($p->separatorHiljada !== '' && $p->separatorHiljada !== $p->decimalniSeparator) {
            $broj = str_replace($p->separatorHiljada, '', $broj);
        }

        $broj = str_replace(' ', '', $broj);

        if ($p->decimalniSeparator === ',') {
            $broj = str_replace(',', '.', $broj);
        }

        try {
            $vrednost = Decimal::izTeksta($broj);
        } catch (\InvalidArgumentException) {
            throw new \InvalidArgumentException("{$polje->naziv()}: „{$tekst}” nije broj.");
        }

        return $vrednost !== null && $vrednost < Decimal::nula() ? -$vrednost : $vrednost;
    }

    private function vreme(string $tekst): CarbonImmutable
    {
        $p = $this->podesavanja;
        $zona = new \DateTimeZone($p->vremenskaZona);

        if ($p->formatDatuma === 'iso') {
            try {
                return CarbonImmutable::parse($tekst, $zona)->utc();
            } catch (Throwable) {
                throw new \InvalidArgumentException("datum „{$tekst}” nije u ISO 8601 formatu.");
            }
        }

        // "!" postavlja nepostojeće delove (npr. vreme kod formata samo sa datumom) na nulu.
        $vreme = \DateTimeImmutable::createFromFormat('!'.$p->formatDatuma, $tekst, $zona);
        $greske = \DateTimeImmutable::getLastErrors();

        if ($vreme === false || ($greske !== false && ($greske['warning_count'] > 0 || $greske['error_count'] > 0))) {
            $primer = PodesavanjaUvoza::FORMATI_DATUMA[$p->formatDatuma];

            throw new \InvalidArgumentException("datum „{$tekst}” nije u formatu {$primer}.");
        }

        return CarbonImmutable::instance($vreme)->utc();
    }
}
