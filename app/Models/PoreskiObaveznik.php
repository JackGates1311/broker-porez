<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Podaci o poreskom obavezniku, Deo 2 obrazaca PPDG-3R (polja 2.1-2.10) i PP OPO.
 */
#[Fillable([
    'tip_obaveznika', 'pib', 'ime_prezime', 'prebivaliste', 'adresa',
    'telefon', 'email', 'jmbg_podnosioca', 'zemlja_rezidentstva', 'pib_punomocnika',
])]
class PoreskiObaveznik extends Model
{
    const CREATED_AT = null;

    const UPDATED_AT = 'azurirano_at';

    /** Polje 2.1, oznake iz uputstva za PPDG-3R. */
    public const TIPOVI = [
        1 => 'Rezidentno fizičko lice',
        2 => 'Preduzetnik koji porez plaća na paušalno utvrđen prihod',
        3 => 'Nerezidentno fizičko lice koje ima boravište u Republici Srbiji',
        4 => 'Nerezidentno fizičko lice koje nema boravište u Republici Srbiji',
    ];

    /** Polja koja svaki obaveznik mora da popuni (2.9 samo nerezident, 2.10 je opciono). */
    public const OBAVEZNA_POLJA = [
        'tip_obaveznika', 'pib', 'ime_prezime', 'prebivaliste', 'adresa',
        'telefon', 'email', 'jmbg_podnosioca',
    ];

    protected $table = 'poreski_obaveznik';

    protected $primaryKey = 'korisnik_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'tip_obaveznika' => 'integer',
        ];
    }

    public function jeNerezident(): bool
    {
        return in_array($this->tip_obaveznika, [3, 4], true);
    }

    public function jePopunjen(): bool
    {
        foreach (self::OBAVEZNA_POLJA as $polje) {
            if (blank($this->{$polje})) {
                return false;
            }
        }

        return ! $this->jeNerezident() || filled($this->zemlja_rezidentstva);
    }
}
