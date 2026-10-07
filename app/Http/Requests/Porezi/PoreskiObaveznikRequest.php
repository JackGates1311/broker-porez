<?php

namespace App\Http\Requests\Porezi;

use App\Models\PoreskiObaveznik;
use App\Support\Drzave;
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
     * Polje 2.9 se bira kao "Srbija (RS)", a čuva se samo oznaka.
     */
    protected function prepareForValidation(): void
    {
        if (filled($this->input('zemlja_rezidentstva'))) {
            $this->merge([
                'zemlja_rezidentstva' => Drzave::oznaka($this->input('zemlja_rezidentstva')) ?? $this->input('zemlja_rezidentstva'),
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
            'adresa' => ['required', 'string', 'max:255'],
            // Cifre i uobičajeni separatori (+ samo na početku), od 6 do 15 cifara (E.164).
            'telefon' => ['required', 'string', 'max:30', 'regex:/^\+?(?=(?:\D*\d){6,15}\D*$)[\d ()\/.-]+$/'],
            // Strogo po RFC-u i filteru PHP-a, uz domen sa nastavkom od bar dva slova (odbija a@b, ime@localhost).
            'email' => ['required', 'string', 'max:100', 'email:rfc,strict,filter', 'regex:/@[^@\s]+\.[A-Za-z]{2,}$/'],
            'jmbg_podnosioca' => ['required', 'digits:13'],
            'zemlja_rezidentstva' => ['nullable', 'required_if:tip_obaveznika,3,4', Rule::in(array_keys(config('drzave')))],
            'pib_punomocnika' => ['nullable', 'regex:/^(\d{9}|\d{13})$/'],
        ];
    }

    /**
     * Polja 2.9 i 2.10 važe samo za nerezidente, pa se rezidentima ne čuvaju.
     *
     * @return array<string, mixed>
     */
    public function podaci(): array
    {
        $podaci = $this->validated();
        $tip = (int) $podaci['tip_obaveznika'];

        $podaci['tip_obaveznika'] = $tip;
        $podaci['zemlja_rezidentstva'] = in_array($tip, [3, 4], true) ? $podaci['zemlja_rezidentstva'] : null;
        $podaci['pib_punomocnika'] = $tip === 4 ? ($podaci['pib_punomocnika'] ?? null) : null;

        return $podaci;
    }
}
