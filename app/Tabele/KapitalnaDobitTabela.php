<?php

namespace App\Tabele;

use App\Enums\TipTransakcije;
use App\Support\Tabela\Kolona;
use App\Support\Tabela\Tabela;
use Illuminate\Http\Request;

/**
 * Kupovine i prodaje na stranici Kapitalna dobit. Izračunate kolone (vrednost, nabavna,
 * dobit, porez, obaveza) su SQL izrazi sa istim formulama kao KapitalnaDobitIzvestaj,
 * da bi se po njima pretraživalo i sortiralo u bazi. Upit mora da spoji imovinu kao "i".
 */
final class KapitalnaDobitTabela
{
    public static function za(Request $zahtev): Tabela
    {
        $prodaja = SqlIzraz::jeTipa(TipTransakcije::Prodaja);
        $kupovina = SqlIzraz::jeTipa(TipTransakcije::Kupovina);
        $stopa = SqlIzraz::broj(config('porezi.stopa'));

        $vrednost = 'transakcije.kolicina * transakcije.cena_po_akciji * transakcije.kurs';
        $alocirano = '(SELECT COALESCE(SUM(a.nabavna_vrednost_rsd), 0) FROM alokacije_lotova_prodaje a'
            .' WHERE a.transakcija_prodaje_id = transakcije.id AND a.korisnik_id = transakcije.korisnik_id)';
        $nabavna = "CASE WHEN {$prodaja} THEN {$alocirano} END";
        $dobit = "CASE WHEN {$prodaja} AND transakcije.kurs IS NOT NULL THEN {$vrednost} - {$alocirano} END";
        $porez = "({$dobit}) * {$stopa}";
        $preostalo = "CASE WHEN {$kupovina} THEN (SELECT MAX(l.preostala_kolicina) FROM poreski_lotovi l"
            .' WHERE l.transakcija_kupovine_id = transakcije.id AND l.korisnik_id = transakcije.korisnik_id) END';

        return Tabela::od([
            Kolona::enumeracija('tip', 'Tip')
                ->opcija(TipTransakcije::Kupovina->value, TipTransakcije::Kupovina->naziv(), ...TipTransakcije::Kupovina->sqlUslov('transakcije.tip_akcije'))
                ->opcija(TipTransakcije::Prodaja->value, TipTransakcije::Prodaja->naziv(), ...TipTransakcije::Prodaja->sqlUslov('transakcije.tip_akcije')),
            Kolona::datum('vreme', 'Datum i vreme (SRB)')->izraz('transakcije.vreme_utc'),
            Kolona::tekst('simbol', 'Simbol')->izraz('i.simbol')->iTrazi('i.naziv', 'i.isin'),
            Kolona::decimalni('kolicina', 'Količina', 8)->izraz('transakcije.kolicina'),
            Kolona::decimalni('cena', 'Cena', 4)->izraz('transakcije.cena_po_akciji'),
            Kolona::tekst('valuta', 'Valuta')->izraz('transakcije.valuta_cene'),
            Kolona::decimalni('kurs', 'Kurs NBS', 4)->izraz('transakcije.kurs'),
            Kolona::decimalni('vrednost', 'Vrednost (RSD)')->izraz($vrednost),
            Kolona::decimalni('preostalo', 'Preostalo (FIFO)', 8)->izraz($preostalo),
            Kolona::decimalni('nabavna', 'Nabavna vrednost')->izraz($nabavna),
            Kolona::decimalni('dobit', 'Dobit / gubitak')->izraz($dobit),
            Kolona::decimalni('porez', 'Porez 15%')->izraz($porez),
            Kolona::decimalni('obaveza', 'Obaveza')->izraz("CASE WHEN ({$porez}) IS NULL THEN NULL WHEN ({$porez}) > 0 THEN ({$porez}) ELSE 0 END"),
        ])
            ->podrazumevano('vreme')
            ->tiebreak('transakcije.id')
            ->izZahteva($zahtev);
    }
}
