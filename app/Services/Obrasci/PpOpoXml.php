<?php

namespace App\Services\Obrasci;

use App\Models\PoreskiObaveznik;
use App\Services\Obrasci\Xml\PrijavaXml;
use App\Support\Decimal;
use DOMDocument;

/**
 * PP OPO za "XML unos" na portalu ePorezi, po zvaničnoj šemi Poreske uprave
 * (resources/xsd/PPOPO-Prijava.xsd) i njenom primeru PPOPO.XML.
 */
final class PpOpoXml
{
    public const SEMA = __DIR__.'/../../../resources/xsd/PPOPO-Prijava.xsd';

    /**
     * Iznosi se zaokružuju na pare pre poređenja, pa je za uplatu tačno
     * max(0, obračunati − plaćeni u inostranstvu), kako portal i proverava.
     *
     * @param  object  $red  red iz DividendeIzvestaj::red(), sa kursom
     * @param  array{vrsta_prijave: string, sifra_vrste_prihoda: string, nacin_ostvarivanja: string}  $sifre
     */
    public static function napravi(object $red, PoreskiObaveznik $obaveznik, array $sifre): string
    {
        $bruto = $red->obracun->brutoRsd->round(2);
        $porez = $red->obracun->porez->round(2);
        $placeno = $red->obracun->placenPorezRsd->round(2);
        $zaUplatu = Decimal::max(Decimal::nula(), $porez - $placeno);
        $nula = Decimal::nula();

        $xml = new PrijavaXml;

        $xml->otvori('PodaciOPrijavi')
            ->element('VrstaPrijave', $sifre['vrsta_prijave'])
            ->element('ObracunskiPeriod', $red->vreme->format('Y-m'))
            ->datum('DatumOstvarivanjaPrihoda', $red->vreme)
            ->element('Rok', 1)
            ->datum('DatumDospelostiObaveze', $red->vreme->startOfDay()->addDays(30))
            ->zatvori();

        $xml->otvori('PodaciOPoreskomObvezniku')
            ->element('PoreskiIdentifikacioniBroj', (string) $obaveznik->pib)
            ->cdata('ImePrezimeObveznika', (string) $obaveznik->ime_prezime)
            ->cdata('UlicaBrojPoreskogObveznika', (string) $obaveznik->adresa)
            ->element('PrebivalisteOpstina', (string) $obaveznik->opstina_sifra)
            ->element('JMBGPodnosiocaPrijave', (string) $obaveznik->jmbg_podnosioca)
            ->element('TelefonKontaktOsobe', (string) $obaveznik->telefon)
            ->cdata('ElektronskaPosta', (string) $obaveznik->email);

        if (filled($obaveznik->zemlja_rezidentstva)) {
            $xml->element('DrzavaRezidenstva', $obaveznik->zemlja_rezidentstva);
        }

        if (filled($obaveznik->broj_pasosa)) {
            $xml->cdata('BrojPasosa', $obaveznik->broj_pasosa);
        }

        if (filled($obaveznik->pib_punomocnika)) {
            $xml->element('BrojPoreskogPunomocnika', $obaveznik->pib_punomocnika);
        }

        $xml->zatvori();

        $xml->otvori('PodaciONacinuOstvarivanjaPrihoda')
            ->element('NacinIsplate', $sifre['nacin_ostvarivanja']);

        // Tekući račun ide uz isplatu na račun, isto kao polje 3.2 na PDF-u.
        if ((string) $sifre['nacin_ostvarivanja'] === '1' && filled($obaveznik->tekuci_racun)) {
            $xml->element('TekuciRacun', $obaveznik->tekuci_racun);
        }

        if ((string) $sifre['nacin_ostvarivanja'] === '3') {
            $xml->cdata('Ostalo', $red->transakcija->izvor()->naziv());
        }

        $xml->zatvori();

        $xml->otvori('DeklarisaniPodaciOVrstamaPrihoda')
            ->otvori('PodaciOVrstamaPrihoda')
            ->element('RedniBroj', 1)
            ->element('SifraVrstePrihoda', $sifre['sifra_vrste_prihoda'])
            ->iznos('BrutoPrihod', $bruto)
            ->iznos('OsnovicaZaPorez', $bruto)
            ->iznos('ObracunatiPorez', $porez)
            ->iznos('PorezPlacenDrugojDrzavi', $placeno)
            ->iznos('PorezZaUplatu', $zaUplatu)
            ->zatvori()
            ->zatvori();

        $xml->otvori('Ukupno')
            ->iznos('FondSati', $nula)
            ->iznos('BrutoPrihod', $bruto)
            ->iznos('OsnovicaZaPorez', $bruto)
            ->iznos('ObracunatiPorez', $porez)
            ->iznos('PorezPlacenDrugojDrzavi', $placeno)
            ->iznos('PorezZaUplatu', $zaUplatu)
            ->iznos('OsnovicaZaDoprinose', $nula)
            ->iznos('PIO', $nula)
            ->iznos('ZDRAVSTVO', $nula)
            ->iznos('NEZAPOSLENOST', $nula)
            ->zatvori();

        $xml->otvori('Kamata')
            ->iznos('PorezZaUplatu', $nula)
            ->iznos('OsnovicaZaDoprinose', $nula)
            ->iznos('PIO', $nula)
            ->iznos('ZDRAVSTVO', $nula)
            ->iznos('NEZAPOSLENOST', $nula)
            ->zatvori();

        $xml->otvori('PodaciODodatnojKamati')->zatvori();

        return $xml->xml();
    }

    /**
     * Greške provere prema zvaničnoj šemi; prazna lista znači da je XML ispravan.
     *
     * @return list<string>
     */
    public static function proveri(string $xml): array
    {
        $prethodno = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $dokument = new DOMDocument;

            if ($dokument->loadXML($xml)) {
                $dokument->schemaValidate(self::SEMA);
            }

            return array_values(array_unique(array_map(
                fn ($greska) => str_replace('{'.PrijavaXml::PROSTOR_IMENA.'}', '', trim($greska->message)),
                libxml_get_errors(),
            )));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prethodno);
        }
    }
}
