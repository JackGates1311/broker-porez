<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['datum', 'valuta', 'srednji_kurs'])]
class Kurs extends Model
{
    public $timestamps = false;

    protected $table = 'kursevi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datum' => 'date',
        ];
    }
}
