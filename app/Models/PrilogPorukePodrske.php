<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fajl priložen uz poruku podrške; sam fajl je na disku local (putanja), ovde su samo podaci o njemu.
 */
class PrilogPorukePodrske extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'prilozi_poruka_podrske';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<PorukaPodrske, $this>
     */
    public function poruka(): BelongsTo
    {
        return $this->belongsTo(PorukaPodrske::class, 'poruka_id');
    }
}
