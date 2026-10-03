<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['korisnik_id', 'kod_hash', 'broj_pokusaja', 'istice_at'])]
#[Hidden(['kod_hash'])]
class VerifikacioniKod extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'verifikacioni_kodovi';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'broj_pokusaja' => 'integer',
            'istice_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Korisnik, $this>
     */
    public function korisnik(): BelongsTo
    {
        return $this->belongsTo(Korisnik::class, 'korisnik_id');
    }

    public function jeIstekao(): bool
    {
        return $this->istice_at->isPast();
    }
}
