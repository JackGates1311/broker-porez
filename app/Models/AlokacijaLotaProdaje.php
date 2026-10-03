<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokacijaLotaProdaje extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'alokacije_lotova_prodaje';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<PoreskiLot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(PoreskiLot::class, 'poreski_lot_id');
    }
}
