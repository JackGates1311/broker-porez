<?php

namespace App\Support\Tabela;

/**
 * Tip podatka kolone; od njega zavisi kako se tumači tekst pretrage i kako se sortira.
 */
enum TipKolone
{
    case Tekst;
    case Ceo;
    case Decimalni;
    case Datum;
    case Enum;

    /** Dugmad i linkovi u redu: ne sortira se i ne pretražuje. */
    case Akcija;
}
