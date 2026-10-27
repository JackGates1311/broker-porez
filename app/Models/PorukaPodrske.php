<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Poruka iz widgeta za povratne informacije (samo insajderi), uz mejl na config('podrska.email').
 */
class PorukaPodrske extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'poruke_podrske';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'poslato_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Korisnik, $this>
     */
    public function korisnik(): BelongsTo
    {
        return $this->belongsTo(Korisnik::class, 'korisnik_id');
    }

    /**
     * @return HasMany<PrilogPorukePodrske, $this>
     */
    public function prilozi(): HasMany
    {
        return $this->hasMany(PrilogPorukePodrske::class, 'poruka_id');
    }
}
