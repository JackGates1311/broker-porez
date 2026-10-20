<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Podaci o poreskom obavezniku, Deo 2 obrazaca PPDG-3R (polja 2.1-2.10) i PP OPO,
 * uz tekući račun (PP OPO 3.2) i broj pasoša (PP OPO 2.9).
 */
#[Fillable([
    'tip_obaveznika', 'pib', 'ime_prezime', 'prebivaliste', 'opstina_sifra', 'adresa',
    'telefon', 'email', 'jmbg_podnosioca', 'zemlja_rezidentstva', 'pib_punomocnika',
    'tekuci_racun', 'broj_pasosa',
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

    /**
     * Polja koja svaki obaveznik mora da popuni. Osetljiva polja (2.2, 2.4 prebivalište, 2.5 adresa, 2.6 telefon,
     * 2.8, tekući račun, broj pasoša) i 2.10 su opciona: PDF ih ostavlja praznim, a XML traži ZA_XML.
     */
    public const OBAVEZNA_POLJA = [
        'tip_obaveznika', 'ime_prezime', 'opstina_sifra', 'email', 'zemlja_rezidentstva',
    ];

    /** Bez ovih polja portal ePorezi ne prima prijavu, pa se XML ne pravi. */
    public const ZA_XML = [
        'pib' => '2.2 poreski identifikacioni broj (JMBG/EBS/PIB)',
        'jmbg_podnosioca' => '2.8 JMBG podnosioca prijave',
    ];

    protected $table = 'poreski_obaveznik';

    protected $primaryKey = 'korisnik_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'tip_obaveznika' => 'integer',
            // Identifikatori i račun su u bazi šifrovani (APP_KEY); videti php artisan obaveznik:sifruj.
            'pib' => 'encrypted',
            'jmbg_podnosioca' => 'encrypted',
            'tekuci_racun' => 'encrypted',
            'broj_pasosa' => 'encrypted',
        ];
    }

    public function jePopunjen(): bool
    {
        foreach (self::OBAVEZNA_POLJA as $polje) {
            if (blank($this->{$polje})) {
                return false;
            }
        }

        return true;
    }

    /**
     * Nazivi polja iz ZA_XML koja nisu uneta.
     *
     * @return list<string>
     */
    public function nedostajeZaXml(): array
    {
        return array_values(array_filter(self::ZA_XML, fn (string $naziv, string $polje) => blank($this->{$polje}), ARRAY_FILTER_USE_BOTH));
    }
}
