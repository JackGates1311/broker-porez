<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoreskiLot extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'poreski_lotovi';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Transakcija, $this>
     */
    public function kupovina(): BelongsTo
    {
        return $this->belongsTo(Transakcija::class, 'transakcija_kupovine_id');
    }

    /**
     * @return BelongsTo<Imovina, $this>
     */
    public function imovina(): BelongsTo
    {
        return $this->belongsTo(Imovina::class, 'imovina_id');
    }
}
