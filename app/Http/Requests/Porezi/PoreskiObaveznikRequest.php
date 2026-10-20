<?php

namespace App\Http\Requests\Porezi;

use App\Models\PoreskiObaveznik;
use App\Support\Drzave;
use App\Support\Opstine;
use App\Support\TekuciRacun;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PoreskiObaveznikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Polje 2.9 se bira kao "Srbija (RS)", a opština kao "Grad Novi Sad (223)"; čuvaju se oznaka i šifra.
     * Tekući račun se unosi sa crticama ili bez njih, a čuva kao 18 cifara.
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

        if (filled($this->input('tekuci_racun'))) {
            $this->merge([
                'tekuci_racun' => TekuciRacun::normalizuj($this->input('tekuci_racun')) ?? $this->input('tekuci_racun'),
            ]);
        }

        if (filled($this->input('broj_pasosa'))) {
            $this->merge(['broj_pasosa' => mb_strtoupper(preg_replace('/\s+/', '', $this->input('broj_pasosa')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tip_obaveznika' => ['required', 'integer', Rule::in(array_keys(PoreskiObaveznik::TIPOVI))],
            'pib' => ['nullable', 'regex:/^(\d{9}|\d{13})$/'],
            'ime_prezime' => ['required', 'string', 'max:150'],
            'prebivaliste' => ['nullable', 'string', 'max:255'],
            'opstina_sifra' => ['required', Rule::in(array_keys(config('opstine')))],
            'adresa' => ['nullable', 'string', 'max:255'],
            // Cifre i uobičajeni separatori (+ samo na početku), od 6 do 15 cifara (E.164).
            'telefon' => ['nullable', 'string', 'max:30', 'regex:/^\+?(?=(?:\D*\d){6,15}\D*$)[\d ()\/.-]+$/'],
            // Strogo po RFC-u i filteru PHP-a, uz domen sa nastavkom od bar dva slova (odbija a@b, ime@localhost).
            'email' => ['required', 'string', 'max:100', 'email:rfc,strict,filter', 'regex:/@[^@\s]+\.[A-Za-z]{2,}$/'],
            'jmbg_podnosioca' => ['nullable', 'digits:13'],
            'zemlja_rezidentstva' => ['required', Rule::in(array_keys(config('drzave')))],
            'pib_punomocnika' => ['nullable', 'regex:/^(\d{9}|\d{13})$/'],
            'tekuci_racun' => ['bail', 'nullable', 'digits:18', function (string $atribut, mixed $vrednost, Closure $greska) {
                if (! TekuciRacun::kontrolniBrojJeIspravan((string) $vrednost)) {
                    $greska(__('validation.custom.tekuci_racun.kontrolni_broj'));
                }
            }],
            'broj_pasosa' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9]+$/'],
            // Polje se prikazuje samo dok važeća verzija uslova nije prihvaćena.
            'prihvatam_uslove' => $this->user()->prihvatioUslove() ? ['exclude'] : ['accepted'],
        ];
    }

    /**
     * Polje 2.10 važi samo za nerezidente bez boravišta, pa se ostalima ne čuva.
     *
     * @return array<string, mixed>
     */
    public function podaci(): array
    {
        $podaci = Arr::except($this->validated(), 'prihvatam_uslove');
        $tip = (int) $podaci['tip_obaveznika'];

        $podaci['tip_obaveznika'] = $tip;
        $podaci['pib_punomocnika'] = $tip === 4 ? ($podaci['pib_punomocnika'] ?? null) : null;

        return $podaci;
    }
}
