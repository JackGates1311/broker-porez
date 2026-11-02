<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jedno prihvatanje Uslova korišćenja i Politike privatnosti (dokaz pristanka).
 * Važeće prihvatanje aplikacija čita iz korisnici.uslovi_verzija; ovo je istorija.
 */
class PrihvatanjeUslova extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'prihvatanja_uslova';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Korisnik, $this>
     */
    public function korisnik(): BelongsTo
    {
        return $this->belongsTo(Korisnik::class, 'korisnik_id');
    }
}
