<?php

namespace App\Tabele;

use App\Models\Transakcija;
use App\Services\Uvoz\IzvorUvoza;
use App\Support\Decimal;
use App\Support\Tabela\Kolona;
use App\Support\Tabela\Tabela;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

/**
 * "Sve transakcije" na stranici Uvoz: sirovi redovi iz izvoda. Upit mora da spoji
 * imovinu kao "i" (leftJoin), jer se po simbolu pretražuje i sortira.
 */
final class TransakcijeTabela
{
    public static function za(Request $zahtev): Tabela
    {
        // Iznosi bez valute se ne prikazuju (npr. količina kod depozita), pa se ni ne pretražuju.
        $akoIma = fn (string $valuta, string $kolona) => "CASE WHEN transakcije.{$valuta} IS NULL THEN NULL ELSE transakcije.{$kolona} END";

        return Tabela::od([
            Kolona::datum('vreme', 'Datum i vreme (SRB)')
                ->izraz('transakcije.vreme_utc')
                ->klasa('text-nowrap')
                ->prikaz(fn (Transakcija $t) => $t->vremeSrb()->format('d.m.Y H:i:s')),
            Kolona::tekst('akcija', 'Akcija')
                ->izraz('transakcije.tip_akcije')
                ->klasa('text-nowrap')
                ->prikaz(fn (Transakcija $t) => $t->tip_akcije),
            array_reduce(
                IzvorUvoza::cases(),
                fn (Kolona $kolona, IzvorUvoza $izvor) => $kolona->opcija($izvor->value, $izvor->naziv(), 'transakcije.izvor = ?', [$izvor->value]),
                Kolona::enumeracija('izvor', 'Izvor')->klasa('text-nowrap small'),
            )->prikaz(fn (Transakcija $t) => $t->izvor()->naziv()),
            Kolona::tekst('simbol', 'Simbol')
                ->izraz('i.simbol')
                ->iTrazi('i.naziv', 'i.isin')
                ->klasa('fw-semibold')
                ->prikaz(fn (Transakcija $t) => $t->imovina?->simbol),
            Kolona::decimalni('kolicina', 'Količina', 8)
                ->izraz($akoIma('valuta_cene', 'kolicina'))
                ->prikaz(fn (Transakcija $t) => $t->valuta_cene ? Decimal::formatKolicina($t->decimal('kolicina')) : ''),
            Kolona::decimalni('cena', 'Cena', 4)
                ->izraz($akoIma('valuta_cene', 'cena_po_akciji'))
                ->prikaz(fn (Transakcija $t) => $t->valuta_cene ? Decimal::format($t->decimal('cena_po_akciji'), 4).' '.$t->valuta_cene : ''),
            Kolona::decimalni('kurs', 'Kurs NBS', 4)
                ->izraz('transakcije.kurs')
                ->prikaz(fn (Transakcija $t) => match (true) {
                    $t->kurs !== null => Decimal::format($t->decimal('kurs'), 4),
                    $t->valuta_cene !== null => new HtmlString('<span class="text-warning">nema</span>'),
                    default => '',
                }),
            Kolona::decimalni('ukupno', 'Ukupno')
                ->izraz('transakcije.ukupno')
                ->prikaz(fn (Transakcija $t) => Decimal::format($t->decimal('ukupno')).' '.$t->valuta_ukupno),
            Kolona::decimalni('porez', 'Porez po odbitku')
                ->izraz($akoIma('valuta_poreza', 'porez_po_odbitku'))
                ->prikaz(fn (Transakcija $t) => $t->valuta_poreza ? Decimal::format($t->decimal('porez_po_odbitku')).' '.$t->valuta_poreza : ''),
            Kolona::decimalni('naknade', 'Naknade')
                ->izraz($akoIma('valuta_provizije', 'provizija'))
                ->prikaz(fn (Transakcija $t) => $t->valuta_provizije ? Decimal::format($t->decimal('provizija')).' '.$t->valuta_provizije : ''),
            Kolona::tekst('id', 'ID')
                ->izraz('transakcije.broker_transakcija_id')
                ->klasa('text-body-secondary small')
                ->prikaz(fn (Transakcija $t) => $t->broker_transakcija_id),
        ])
            ->podrazumevano('vreme', 'desc')
            ->tiebreak('transakcije.id')
            ->izZahteva($zahtev);
    }
}
