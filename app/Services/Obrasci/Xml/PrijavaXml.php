<?php

namespace App\Services\Obrasci\Xml;

use App\Support\Decimal;
use BcMath\Number;
use Carbon\CarbonInterface;
use XMLWriter;

/**
 * Piše XML poreske prijave za "XML unos" na portalu ePorezi: koren
 * ns1:PodaciPoreskeDeklaracije u prostoru imena http://pid.purs.gov.rs,
 * a svaki element sa prefiksom ns1 (kao zvanični primer PPOPO.XML).
 */
final class PrijavaXml
{
    public const PROSTOR_IMENA = 'http://pid.purs.gov.rs';

    private XMLWriter $pisac;

    public function __construct()
    {
        $this->pisac = new XMLWriter;
        $this->pisac->openMemory();
        $this->pisac->setIndent(true);
        $this->pisac->setIndentString('  ');
        $this->pisac->startDocument('1.0', 'UTF-8');
        $this->pisac->startElement('ns1:PodaciPoreskeDeklaracije');
        $this->pisac->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->pisac->writeAttribute('xmlns:ns1', self::PROSTOR_IMENA);
    }

    /**
     * @param  array<string, string|int>  $atributi
     */
    public function otvori(string $naziv, array $atributi = []): self
    {
        $this->pisac->startElement("ns1:{$naziv}");

        foreach ($atributi as $atribut => $vrednost) {
            $this->pisac->writeAttribute($atribut, (string) $vrednost);
        }

        return $this;
    }

    public function zatvori(): self
    {
        $this->pisac->endElement();

        return $this;
    }

    public function element(string $naziv, string|int $vrednost): self
    {
        $this->pisac->writeElement("ns1:{$naziv}", (string) $vrednost);

        return $this;
    }

    /**
     * Slobodan tekst (ime, adresa, email) ide u CDATA, kao na portalu.
     */
    public function cdata(string $naziv, string $vrednost): self
    {
        $this->pisac->startElement("ns1:{$naziv}");
        $this->pisac->writeCdata(str_replace(']]>', ']]]]><![CDATA[>', $vrednost));
        $this->pisac->endElement();

        return $this;
    }

    public function iznos(string $naziv, Number $vrednost): self
    {
        return $this->element($naziv, Decimal::zaXml($vrednost));
    }

    /**
     * Količina u punoj preciznosti (frakcione akcije), bez suvišnih nula.
     */
    public function kolicina(string $naziv, Number $vrednost): self
    {
        return $this->element($naziv, Decimal::kanonski($vrednost));
    }

    public function datum(string $naziv, CarbonInterface $datum): self
    {
        return $this->element($naziv, $datum->format('Y-m-d'));
    }

    public function xml(): string
    {
        $this->pisac->endElement();
        $this->pisac->endDocument();

        return $this->pisac->outputMemory();
    }
}
