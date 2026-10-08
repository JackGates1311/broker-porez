<?php

namespace App\Services\Uvoz\Rucni;

/**
 * Ciljna polja transakcije na koja se u čarobnjaku mapiraju kolone CSV-a.
 * Redosled slučajeva je redosled u koraku "Kolone".
 */
enum PoljeUvoza: string
{
    case Vreme = 'vreme';
    case Akcija = 'akcija';
    case Isin = 'isin';
    case Simbol = 'simbol';
    case Naziv = 'naziv';
    case Kolicina = 'kolicina';
    case Cena = 'cena';
    case ValutaCene = 'valuta_cene';
    case Ukupno = 'ukupno';
    case ValutaUkupno = 'valuta_ukupno';
    case Porez = 'porez';
    case ValutaPoreza = 'valuta_poreza';
    case Provizija = 'provizija';
    case ValutaProvizije = 'valuta_provizije';
    case Napomena = 'napomena';
    case BrokerId = 'broker_id';

    public function naziv(): string
    {
        return match ($this) {
            self::Vreme => 'Datum i vreme',
            self::Akcija => 'Akcija (tip transakcije)',
            self::Isin => 'ISIN',
            self::Simbol => 'Simbol (ticker)',
            self::Naziv => 'Naziv hartije',
            self::Kolicina => 'Količina',
            self::Cena => 'Cena po akciji',
            self::ValutaCene => 'Valuta cene',
            self::Ukupno => 'Ukupan iznos',
            self::ValutaUkupno => 'Valuta ukupnog iznosa',
            self::Porez => 'Porez po odbitku',
            self::ValutaPoreza => 'Valuta poreza',
            self::Provizija => 'Provizija / naknade',
            self::ValutaProvizije => 'Valuta provizije',
            self::Napomena => 'Napomena',
            self::BrokerId => 'ID transakcije kod brokera',
        };
    }

    public function pomoc(): ?string
    {
        return match ($this) {
            self::Isin => 'Obavezan za kupovine, prodaje i dividende.',
            self::Kolicina, self::Cena => 'Obavezno za kupovine i prodaje. Za dividendu: broj akcija i neto iznos po akciji.',
            self::ValutaCene => 'Obavezna za kupovine, prodaje i dividende; po njoj se uzima kurs NBS.',
            self::Ukupno => 'Za dividendu bez količine i cene: neto iznos posle poreza po odbitku. Za depozit: uplaćen iznos.',
            default => null,
        };
    }

    public function obavezno(): bool
    {
        return $this === self::Vreme || $this === self::Akcija;
    }

    /**
     * Polje koje može da ima istu vrednost za ceo fajl (npr. valuta "USD" kad je fajl nema).
     */
    public function dozvoljavaKonstantu(): bool
    {
        return in_array($this, [self::ValutaCene, self::ValutaUkupno, self::ValutaPoreza, self::ValutaProvizije], true);
    }

    /**
     * Uobičajeni nazivi zaglavlja (mala slova, bez razmaka i interpunkcije) za predlog mapiranja.
     *
     * @return list<string>
     */
    public function predloziZaglavlja(): array
    {
        return match ($this) {
            self::Vreme => ['timeutc', 'time', 'datetime', 'dateandtime', 'date', 'tradedate', 'datum', 'vreme', 'datumivreme', 'settledate'],
            self::Akcija => ['action', 'type', 'transactiontype', 'side', 'buysell', 'akcija', 'tip', 'operation'],
            self::Isin => ['isin', 'securityid'],
            self::Simbol => ['ticker', 'symbol', 'simbol'],
            self::Naziv => ['name', 'description', 'securityname', 'naziv', 'instrument'],
            self::Kolicina => ['noofshares', 'quantity', 'qty', 'shares', 'units', 'kolicina'],
            self::Cena => ['pricepershare', 'price', 'tradeprice', 'unitprice', 'cena'],
            self::ValutaCene => ['currencypricepershare', 'currency', 'valuta', 'ccy'],
            self::Ukupno => ['total', 'amount', 'netamount', 'proceeds', 'iznos', 'ukupno'],
            self::ValutaUkupno => ['currencytotal', 'currencyamount'],
            self::Porez => ['withholdingtax', 'tax', 'taxes', 'porez'],
            self::ValutaPoreza => ['currencywithholdingtax', 'taxcurrency'],
            self::Provizija => ['commission', 'fee', 'fees', 'commfee', 'provizija'],
            self::ValutaProvizije => ['commissioncurrency', 'feecurrency'],
            self::Napomena => ['notes', 'note', 'comment', 'napomena'],
            self::BrokerId => ['id', 'transactionid', 'tradeid', 'orderid'],
        };
    }
}
