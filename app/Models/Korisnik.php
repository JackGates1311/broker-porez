<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['korisnicko_ime', 'email', 'lozinka_hash'])]
#[Hidden(['lozinka_hash', 'remember_token'])]
class Korisnik extends Authenticatable
{
    use Notifiable;

    const CREATED_AT = 'kreirano_at';

    const UPDATED_AT = null;

    protected $table = 'korisnici';

    /**
     * Kolona sa hešom lozinke koju koristi Laravel autentifikacija.
     */
    protected $authPasswordName = 'lozinka_hash';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verifikovan_at' => 'datetime',
            'lozinka_hash' => 'hashed',
        ];
    }

    /**
     * @return HasMany<VerifikacioniKod, $this>
     */
    public function verifikacioniKodovi(): HasMany
    {
        return $this->hasMany(VerifikacioniKod::class, 'korisnik_id');
    }

    /**
     * @return HasMany<TokenResetLozinke, $this>
     */
    public function tokeniResetLozinke(): HasMany
    {
        return $this->hasMany(TokenResetLozinke::class, 'korisnik_id');
    }

    /**
     * @return HasMany<Transakcija, $this>
     */
    public function transakcije(): HasMany
    {
        return $this->hasMany(Transakcija::class, 'korisnik_id');
    }

    /**
     * @return HasMany<PoreskiLot, $this>
     */
    public function poreskiLotovi(): HasMany
    {
        return $this->hasMany(PoreskiLot::class, 'korisnik_id');
    }

    /**
     * @return HasOne<PoreskiObaveznik, $this>
     */
    public function poreskiObaveznik(): HasOne
    {
        return $this->hasOne(PoreskiObaveznik::class, 'korisnik_id');
    }

    public function jeVerifikovan(): bool
    {
        return $this->email_verifikovan_at !== null;
    }
}
