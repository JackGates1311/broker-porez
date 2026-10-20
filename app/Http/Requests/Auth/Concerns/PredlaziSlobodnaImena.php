<?php

namespace App\Http\Requests\Auth\Concerns;

use App\Services\PredloziKorisnickogImenaService;
use Illuminate\Contracts\Validation\Validator;

/**
 * Kada je korisničko ime ispravno, ali zauzeto, uz grešku se šalju slobodna slična imena.
 */
trait PredlaziSlobodnaImena
{
    protected function failedValidation(Validator $validator): void
    {
        if (array_keys($validator->failed()['korisnicko_ime'] ?? []) === ['Unique']) {
            $this->session()->flash(
                'predlozi_korisnickog_imena',
                app(PredloziKorisnickogImenaService::class)->predlozi($this->string('korisnicko_ime')->toString()),
            );
        }

        parent::failedValidation($validator);
    }
}
