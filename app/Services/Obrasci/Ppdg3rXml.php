<?php

namespace App\Services\Obrasci;

use App\Models\PoreskiObaveznik;
use App\Services\Obrasci\Xml\PrijavaXml;
use App\Services\Porezi\Period;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * PPDG-3R za "XML unos" na portalu ePorezi.
 *
 * Poreska uprava ne objavljuje šemu za PPDG-3R. Struktura je preuzeta iz alata čije
 * XML fajlove portal prihvata: Deo 4 kao ppdg3r.rs (jedna prodaja = jedan unos sa
 * po jednim <Sticanje> za svaki FIFO lot, kao na papirnom obrascu), a redosled
 * elemenata u <Sticanje> i IsplataUDelovima kao ibkr-porez. Zbirove (Deo 5–6) računa
 * portal, a prilog (izveštaj brokera) korisnik otprema na portalu.
 */
final class Ppdg3rXml
{
    /**
     * Prodaje zbog kojih bi iznosi u prijavi bili netačni (bez kursa ili bez uvezenih kupovina).
     *
     * @param  Collection<int, object>  $prodaje  redovi prodaja iz KapitalnaDobitIzvestaj
     * @return list<string>
     */
    public static function prepreke(Collection $prodaje): array
    {
        $prepreke = [];

        foreach ($prodaje as $p) {
            $oznaka = ($p->simbol ?? $p->naziv).' ('.$p->vreme->format('d.m.Y').')';

            if ($p->bez_kursa) {
                $prepreke[] = "{$oznaka}: nedostaje NBS kurs prodaje ili neke od kupovina.";
            }

            if ($p->nedostaje !== null) {
                $prepreke[] = "{$oznaka}: nedostaju uvezene kupovine za ".Decimal::formatKolicina($p->nedostaje).' kom.';
            }
        }

        return $prepreke;
    }

    /**
     * @param  Collection<int, object>  $prodaje  redovi prodaja iz KapitalnaDobitIzvestaj, bez prepreka
     * @param  array{vrsta_prijave: string, osnov_za_prijavu: string}  $sifre
     */
    public static function napravi(Period $period, Collection $prodaje, PoreskiObaveznik $obaveznik, array $sifre, CarbonInterface $danas): string
    {
        $xml = new PrijavaXml;

        $xml->otvori('PodaciOPrijavi')
            ->element('VrstaPrijave', $sifre['vrsta_prijave'])
            ->element('OsnovZaPrijavu', $sifre['osnov_za_prijavu'])
            ->datum('DatumOstvarenjaPrihodaDelaPrihoda', $period->do)
            ->element('IsplataUDelovima', 0)
            ->datum('DatumDospelostiZaPodnosenjePoreskePrijave', $period->datumDospelosti())
            ->otvori('DatumINacinPodnosenjaPrijave')
            ->datum('DatumPodnosenjaPrijave', $danas)
            ->element('NacinPodnosenjaPrijave', 'E')
            ->zatvori()
            ->zatvori();

        $xml->otvori('PodaciOPoreskomObvezniku')
            ->element('TipPoreskogObveznika', (int) $obaveznik->tip_obaveznika)
            ->element('PoreskiIdentifikacioniBroj', (string) $obaveznik->pib)
            ->cdata('ImeIPrezimePoreskogObveznika', (string) $obaveznik->ime_prezime)
            ->element('PrebivalisteBoravistePoreskogObveznika', (string) $obaveznik->opstina_sifra)
            ->cdata('AdresaPoreskogObveznika', (string) $obaveznik->adresa)
            ->element('TelefonKontaktOsobe', (string) $obaveznik->telefon)
            ->cdata('ElektronskaPosta', (string) $obaveznik->email)
            ->element('JMBGPodnosiocaPrijave', (string) $obaveznik->jmbg_podnosioca)
            ->zatvori();

        $xml->otvori('DeklarisanoPrenosHOVInvesticionihJed');

        foreach ($prodaje->values() as $i => $p) {
            $xml->otvori('PodaciOPrenosuHOVInvesticionihJed', ['id' => $i + 1])
                ->element('RedniBroj', $i + 1)
                ->cdata('NazivEmitenta', (string) ($p->simbol ?? $p->naziv))
                ->datum('DatumPrenosaHOV', $p->vreme)
                ->element('BrojDokumentaOPrenosu', 1)
                ->kolicina('BrojPrenetihHOVInvesticionihJed', $p->kolicina)
                ->iznos('ProdajnaCena', $p->vrednost_rsd);

            foreach ($p->alokacije as $lot) {
                $xml->otvori('Sticanje')
                    ->datum('DatumSticanja', $lot->vreme)
                    ->iznos('NabavnaCena', $lot->nabavna_rsd)
                    ->element('BrojDokumentaOSticanju', 1)
                    ->kolicina('BrojStecenihHOVInvesticionihJed', $lot->kolicina)
                    ->zatvori();
            }

            $xml->zatvori();
        }

        $xml->zatvori();

        return $xml->xml();
    }
}
