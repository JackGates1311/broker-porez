<?php

namespace App\Models;

use App\Support\Pravno;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['korisnicko_ime', 'email', 'lozinka_hash', 'google_id'])]
#[Hidden(['lozinka_hash', 'remember_token', 'google_id'])]
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

    protected static function booted(): void
    {
        // Prvo pitanje o zadovoljstvu je moguće odmah. Vreme upisuje aplikacija (UTC), da ne zavisi od DEFAULT-a baze
        // (CURRENT_TIMESTAMP je u zoni sesije; config/database.php je drži u UTC-u).
        static::creating(function (Korisnik $korisnik) {
            if (! array_key_exists('zadovoljstvo_od', $korisnik->getAttributes())) {
                $korisnik->zadovoljstvo_od = now();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verifikovan_at' => 'datetime',
            'uslovi_prihvaceni_at' => 'datetime',
            'lozinka_hash' => 'hashed',
            'is_insajder' => 'boolean',
            'zadovoljstvo_od' => 'datetime',
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
     * @return HasMany<SablonUvoza, $this>
     */
    public function sabloniUvoza(): HasMany
    {
        return $this->hasMany(SablonUvoza::class, 'korisnik_id');
    }

    /**
     * @return HasOne<PoreskiObaveznik, $this>
     */
    public function poreskiObaveznik(): HasOne
    {
        return $this->hasOne(PoreskiObaveznik::class, 'korisnik_id');
    }

    /**
     * @return HasMany<OcenaZadovoljstva, $this>
     */
    public function oceneZadovoljstva(): HasMany
    {
        return $this->hasMany(OcenaZadovoljstva::class, 'korisnik_id');
    }

    /**
     * @return HasMany<PorukaPodrske, $this>
     */
    public function porukePodrske(): HasMany
    {
        return $this->hasMany(PorukaPodrske::class, 'korisnik_id');
    }

    public function jeVerifikovan(): bool
    {
        return $this->email_verifikovan_at !== null;
    }

    /**
     * Insajder vidi widget za povratne informacije; korisnik ga sam uključuje u Moj nalog (NalogController::insajder, nije fillable).
     */
    public function jeInsajder(): bool
    {
        return (bool) $this->is_insajder;
    }

    /**
     * Da li se posle izvoza obrasca prikazuje pitanje o zadovoljstvu: samo insajderi, kada je prošla pauza
     * (zadovoljstvo_od). NULL znači „Ne pitaj više”.
     */
    public function trebaPitatiZadovoljstvo(): bool
    {
        return $this->jeInsajder()
            && $this->jeVerifikovan()
            && $this->zadovoljstvo_od !== null
            && $this->zadovoljstvo_od->lte(now());
    }

    /**
     * Da li važi verzija Uslova korišćenja i Politike privatnosti koju je korisnik prihvatio
     * (ranija verzija važi do datuma stupanja na snagu nove).
     */
    public function prihvatioUslove(): bool
    {
        return Pravno::vaziPrihvacena($this->uslovi_verzija);
    }

    public function prihvatiUslove(): void
    {
        $this->forceFill([
            'uslovi_prihvaceni_at' => now(),
            'uslovi_verzija' => Pravno::verzija(),
        ])->save();
    }
}
