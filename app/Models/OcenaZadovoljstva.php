<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Odgovor insajdera na pitanje „Koliko ste zadovoljni?” posle izvoza obrasca.
 */
class OcenaZadovoljstva extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    /** Izvoz posle kog je pitano => naziv u izveštaju (data-izvoz-obrasca na dugmadima za izvoz). */
    public const POVODI = [
        'ppdg-3r-pdf' => 'PPDG-3R (PDF)',
        'ppdg-3r-xml' => 'PPDG-3R (XML)',
        'pp-opo-pdf' => 'PP OPO (PDF)',
        'pp-opo-xml' => 'PP OPO (XML)',
    ];

    protected $table = 'ocene_zadovoljstva';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ocena' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Korisnik, $this>
     */
    public function korisnik(): BelongsTo
    {
        return $this->belongsTo(Korisnik::class, 'korisnik_id');
    }
}
