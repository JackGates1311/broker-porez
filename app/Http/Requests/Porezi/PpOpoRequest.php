<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PpOpoRequest extends FormRequest
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
            'vrsta_prijave' => ['nullable', 'digits_between:1,2'],
            'sifra_vrste_prihoda' => ['nullable', 'digits:9'],
            'nacin_ostvarivanja' => ['nullable', 'digits:1'],
        ];
    }
}
