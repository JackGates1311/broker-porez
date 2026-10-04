<?php

namespace App\Tabele;

use App\Services\Porezi\DividendaKalkulator;
use App\Support\Decimal;
use App\Support\Tabela\Kolona;
use App\Support\Tabela\Tabela;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

/**
 * Dividende: redovi su objekti iz DividendeIzvestaj::red(). Izračunate kolone su SQL
 * izrazi sa formulama DividendaKalkulator-a. Upit mora da spoji imovinu kao "i".
 */
final class DividendeTabela
{
    public static function za(Request $zahtev): Tabela
    {
        $stopa = SqlIzraz::broj(DividendaKalkulator::STOPA);

        $porezUInostr = 'COALESCE(transakcije.porez_po_odbitku, 0)';
        $neto = 'transakcije.kolicina * transakcije.cena_po_akciji';
        $bruto = "({$neto} + {$porezUInostr})";
        $brutoRsd = "{$bruto} * transakcije.kurs";
        $porez = "{$brutoRsd} * {$stopa}";
        $placeno = "{$porezUInostr} * COALESCE(transakcije.kurs_porez, transakcije.kurs)";
        $razlika = "({$porez} - {$placeno})";

        return Tabela::od([
            Kolona::datum('vreme', 'Datum isplate (SRB)')
                ->izraz('transakcije.vreme_utc')
                ->klasa('text-nowrap')
                ->prikaz(fn ($r) => $r->vreme->format('d.m.Y')),
            Kolona::tekst('simbol', 'Simbol')
                ->izraz('i.simbol')
                ->iTrazi('i.naziv', 'i.isin')
                ->prikaz(fn ($r) => new HtmlString(
                    '<span class="fw-semibold" title="'.e($r->naziv).'">'.e($r->simbol).'</span>'
                    .'<div class="small text-body-secondary">'.e($r->isin).'</div>'
                )),
            Kolona::decimalni('kolicina', 'Količina', 8)
                ->izraz('transakcije.kolicina')
                ->prikaz(fn ($r) => Decimal::formatKolicina($r->kolicina)),
            Kolona::decimalni('neto_po_akciji', 'Neto po akciji', 4)
                ->izraz('transakcije.cena_po_akciji')
                ->prikaz(fn ($r) => Decimal::format($r->neto_po_akciji, 4)),
            Kolona::decimalni('porez_po_odbitku', 'Porez u inostr.')
                ->izraz('transakcije.porez_po_odbitku')
                ->prikaz(fn ($r) => Decimal::format($r->porez_po_odbitku)),
            Kolona::tekst('valuta', 'Valuta')
                ->izraz('transakcije.valuta_cene'),
            Kolona::decimalni('kurs', 'Kurs NBS', 4)
                ->izraz('transakcije.kurs')
                ->prikaz(fn ($r) => $r->kurs
                    ? Decimal::format($r->kurs, 4)
                    : new HtmlString('<span class="text-warning">nema kursa</span>')),
            Kolona::decimalni('stopa', 'Stopa u inostr.')
                ->izraz("CASE WHEN ROUND({$bruto}, 2) = 0 THEN 0 ELSE {$porezUInostr} * 100 / ROUND({$bruto}, 2) END")
                ->prikaz(fn ($r) => Decimal::format($r->obracun->procenatPoreza * 100).'%'),
            Kolona::decimalni('bruto', 'Bruto')
                ->izraz($bruto)
                ->prikaz(fn ($r) => Decimal::format($r->obracun->bruto)),
            Kolona::decimalni('neto', 'Neto')
                ->izraz($neto)
                ->prikaz(fn ($r) => Decimal::format($r->obracun->neto)),
            Kolona::decimalni('bruto_rsd', 'Bruto (RSD)')
                ->izraz($brutoRsd)
                ->prikaz(fn ($r) => Decimal::format($r->obracun->brutoRsd)),
            Kolona::decimalni('porez', 'Porez 15% (RSD)')
                ->izraz($porez)
                ->prikaz(fn ($r) => Decimal::format($r->obracun->porez)),
            Kolona::decimalni('za_uplatu', 'Za uplatu (RSD)')
                ->izraz("CASE WHEN transakcije.kurs IS NULL THEN NULL WHEN {$razlika} > 0 THEN {$razlika} ELSE 0 END")
                ->klasa('broj fw-semibold')
                ->prikaz(fn ($r) => Decimal::format($r->obracun->zaUplatu)),
            Kolona::akcija('obrazac', 'Obrazac')
                ->skrivenNaslov()
                ->prikaz(fn ($r) => new HtmlString(view('porezi._ppopo-dugme', ['red' => $r])->render())),
        ])
            ->podrazumevano('vreme')
            ->tiebreak('transakcije.id')
            ->klasaReda(fn ($r) => ['upozorenje-red' => $r->bez_kursa])
            ->izZahteva($zahtev);
    }
}
