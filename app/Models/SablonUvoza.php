<?php

namespace App\Models;

use App\Services\Uvoz\Rucni\PodesavanjaUvoza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sačuvano mapiranje ručnog uvoza CSV-a ("Moj broker X"), po korisniku.
 */
class SablonUvoza extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'sabloni_uvoza';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'podesavanja' => 'array',
        ];
    }

    public function podesavanjaUvoza(): PodesavanjaUvoza
    {
        return PodesavanjaUvoza::izNiza($this->podesavanja ?? []);
    }

    /**
     * @return BelongsTo<Korisnik, $this>
     */
    public function korisnik(): BelongsTo
    {
        return $this->belongsTo(Korisnik::class, 'korisnik_id');
    }
}
