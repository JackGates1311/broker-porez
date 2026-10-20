<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Auth\Concerns\PredlaziSlobodnaImena;
use App\Services\PredloziKorisnickogImenaService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Promena korisničkog imena (posle prve Google prijave i u "Moj nalog"), po istim pravilima kao registracija.
 */
class KorisnickoImeRequest extends FormRequest
{
    use PredlaziSlobodnaImena;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['korisnicko_ime' => trim((string) $this->input('korisnicko_ime'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'korisnicko_ime' => [
                'required', 'string', 'alpha_dash', 'max:'.PredloziKorisnickogImenaService::MAKS_DUZINA,
                Rule::unique('korisnici', 'korisnicko_ime')->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'korisnicko_ime.unique' => 'Korisničko ime je već zauzeto.',
        ];
    }
}
