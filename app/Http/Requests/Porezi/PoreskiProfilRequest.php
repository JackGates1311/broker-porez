<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PoreskiProfilRequest extends FormRequest
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
            'jmbg' => ['nullable', 'digits:13'],
            'ime_prezime' => ['nullable', 'string', 'max:150'],
            'adresa' => ['nullable', 'string', 'max:255'],
            'prebivaliste_sifra' => ['nullable', 'digits:3'],
            'telefon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'zemlja_rezidentstva' => ['nullable', 'alpha', 'max:3'],
        ];
    }
}
