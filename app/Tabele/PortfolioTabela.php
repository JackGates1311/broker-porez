<?php

namespace App\Tabele;

use App\Support\Decimal;
use App\Support\Tabela\Kolona;
use App\Support\Tabela\Tabela;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

/**
 * Otvorene pozicije na Pregledu. Upit je agregat po imovini umotan u podupit "p"
 * (DashboardController::portfolio), pa se pretražuje i sortira po njegovim kolonama.
 */
final class PortfolioTabela
{
    public static function za(Request $zahtev): Tabela
    {
        return Tabela::od([
            Kolona::tekst('simbol', 'Simbol')->izraz('p.simbol')->klasa('fw-semibold'),
            Kolona::tekst('naziv', 'Naziv')->izraz('p.naziv'),
            Kolona::tekst('isin', 'ISIN')->izraz('p.isin')->klasa('text-body-secondary'),
            Kolona::decimalni('kolicina', 'Količina', 8)
                ->izraz('p.kolicina')
                ->prikaz(fn ($p) => Decimal::formatKolicina($p->kolicina)),
            Kolona::decimalni('nabavna_rsd', 'Nabavna vrednost (RSD)')
                ->izraz('p.nabavna_rsd')
                ->prikaz(fn ($p) => new HtmlString(e(Decimal::format($p->nabavna_rsd))
                    .($p->bez_kursa ? ' <span class="text-warning" title="Deo kupovina nema NBS kurs">*</span>' : ''))),
        ])
            ->podrazumevano('simbol')
            ->tiebreak('p.id')
            ->izZahteva($zahtev);
    }
}
