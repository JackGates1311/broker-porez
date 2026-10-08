<?php

namespace App\Models;

use App\Enums\TipTransakcije;
use App\Services\Uvoz\IzvorUvoza;
use App\Support\Decimal;
use BcMath\Number;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Decimalne kolone se namerno ne kastuju: MySQL ih vraća kao string, a račun ide
 * preko BcMath\Number (vidi decimal()), nikad preko float-a.
 */
class Transakcija extends Model
{
    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'transakcije';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vreme_utc' => 'immutable_datetime',
        ];
    }

    public function tip(): TipTransakcije
    {
        return TipTransakcije::from($this->tip);
    }

    public function izvor(): IzvorUvoza
    {
        return IzvorUvoza::from($this->izvor);
    }

    public function decimal(string $kolona): ?Number
    {
        $vrednost = $this->getAttribute($kolona);

        return $vrednost === null ? null : Decimal::n($vrednost);
    }

    public function vremeSrb(): CarbonImmutable
    {
        return $this->vreme_utc->setTimezone('Europe/Belgrade');
    }

    /**
     * @param  Builder<self>  $upit
     */
    public function scopeTipa(Builder $upit, TipTransakcije ...$tipovi): void
    {
        $upit->whereIn($upit->qualifyColumn('tip'), array_map(fn (TipTransakcije $t) => $t->value, $tipovi));
    }

    /**
     * @return BelongsTo<Imovina, $this>
     */
    public function imovina(): BelongsTo
    {
        return $this->belongsTo(Imovina::class, 'imovina_id');
    }

    /**
     * @return HasOne<PoreskiLot, $this>
     */
    public function lot(): HasOne
    {
        return $this->hasOne(PoreskiLot::class, 'transakcija_kupovine_id');
    }

    /**
     * @return HasMany<AlokacijaLotaProdaje, $this>
     */
    public function alokacije(): HasMany
    {
        return $this->hasMany(AlokacijaLotaProdaje::class, 'transakcija_prodaje_id');
    }
}
