<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'vrsta_prijave' => ['nullable', Rule::in(array_keys(config('porezi.ppopo.vrste_prijave')))],
            'sifra_vrste_prihoda' => ['nullable', 'digits:9'],
            'nacin_ostvarivanja' => ['nullable', Rule::in(array_keys(config('porezi.ppopo.nacini_ostvarivanja')))],
        ];
    }
}
