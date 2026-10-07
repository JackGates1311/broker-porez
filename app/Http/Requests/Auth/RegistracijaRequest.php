<?php

namespace App\Http\Requests\Auth;

use App\Services\PredloziKorisnickogImenaService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistracijaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'korisnicko_ime' => ['required', 'string', 'alpha_dash', 'max:'.PredloziKorisnickogImenaService::MAKS_DUZINA, 'unique:korisnici,korisnicko_ime'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:korisnici,email'],
            'lozinka' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'korisnicko_ime.unique' => 'Korisničko ime je već zauzeto.',
            'email.unique' => 'Već postoji nalog sa ovom email adresom.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Kada je ime ispravno, ali zauzeto, uz grešku se šalju slobodna slična imena.
     */
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
