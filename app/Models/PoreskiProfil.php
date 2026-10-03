<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['jmbg', 'ime_prezime', 'adresa', 'prebivaliste_sifra', 'telefon', 'email', 'zemlja_rezidentstva'])]
class PoreskiProfil extends Model
{
    const CREATED_AT = null;

    const UPDATED_AT = 'azurirano_at';

    protected $table = 'poreski_profil';

    protected $primaryKey = 'korisnik_id';

    public $incrementing = false;

    public function jePopunjen(): bool
    {
        return filled($this->jmbg) && filled($this->ime_prezime) && filled($this->adresa);
    }
}
