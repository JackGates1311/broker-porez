<?php

namespace App\Http\Requests\Porezi;

use App\Models\PoreskiObaveznik;
use App\Support\Drzave;
use App\Support\Opstine;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PoreskiObaveznikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Polje 2.9 se bira kao "Srbija (RS)", a opština kao "Grad Novi Sad (223)"; čuvaju se oznaka i šifra.
     */
    protected function prepareForValidation(): void
    {
        if (filled($this->input('zemlja_rezidentstva'))) {
            $this->merge([
                'zemlja_rezidentstva' => Drzave::oznaka($this->input('zemlja_rezidentstva')) ?? $this->input('zemlja_rezidentstva'),
            ]);
        }

        if (filled($this->input('opstina_sifra'))) {
            $this->merge([
                'opstina_sifra' => Opstine::sifra($this->input('opstina_sifra')) ?? $this->input('opstina_sifra'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tip_obaveznika' => ['required', 'integer', Rule::in(array_keys(PoreskiObaveznik::TIPOVI))],
            'pib' => ['required', 'regex:/^(\d{9}|\d{13})$/'],
            'ime_prezime' => ['required', 'string', 'max:150'],
            'prebivaliste' => ['required', 'string', 'max:255'],
            'opstina_sifra' => ['required', Rule::in(array_keys(config('opstine')))],
            'adresa' => ['required', 'string', 'max:255'],
            // Cifre i uobičajeni separatori (+ samo na početku), od 6 do 15 cifara (E.164).
            'telefon' => ['required', 'string', 'max:30', 'regex:/^\+?(?=(?:\D*\d){6,15}\D*$)[\d ()\/.-]+$/'],
            // Strogo po RFC-u i filteru PHP-a, uz domen sa nastavkom od bar dva slova (odbija a@b, ime@localhost).
            'email' => ['required', 'string', 'max:100', 'email:rfc,strict,filter', 'regex:/@[^@\s]+\.[A-Za-z]{2,}$/'],
            'jmbg_podnosioca' => ['required', 'digits:13'],
            'zemlja_rezidentstva' => ['required', Rule::in(array_keys(config('drzave')))],
            'pib_punomocnika' => ['nullable', 'regex:/^(\d{9}|\d{13})$/'],
        ];
    }

    /**
     * Polje 2.10 važi samo za nerezidente bez boravišta, pa se ostalima ne čuva.
     *
     * @return array<string, mixed>
     */
    public function podaci(): array
    {
        $podaci = $this->validated();
        $tip = (int) $podaci['tip_obaveznika'];

        $podaci['tip_obaveznika'] = $tip;
        $podaci['pib_punomocnika'] = $tip === 4 ? ($podaci['pib_punomocnika'] ?? null) : null;

        return $podaci;
    }
}
