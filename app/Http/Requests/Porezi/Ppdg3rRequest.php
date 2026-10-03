<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class Ppdg3rRequest extends FormRequest
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
            'godina' => ['required', 'integer', 'between:2000,2100'],
            'polugodiste' => ['required', 'in:1,2'],
            'vrsta_prijave' => ['nullable', 'digits_between:1,2'],
            'osnov_za_prijavu' => ['nullable', 'digits_between:1,2'],
        ];
    }
}
