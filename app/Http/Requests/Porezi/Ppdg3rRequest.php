<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'vrsta_prijave' => ['nullable', Rule::in(array_keys(config('porezi.ppdg3r.vrste_prijave')))],
            'osnov_za_prijavu' => ['nullable', Rule::in(array_keys(config('porezi.ppdg3r.osnovi_za_prijavu')))],
        ];
    }
}
