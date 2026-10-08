<?php

namespace App\Http\Requests\Porezi;

use App\Enums\TipTransakcije;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UvozRucniAkcijeRequest extends FormRequest
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
            'akcije' => ['required', 'array', 'max:200'],
            'akcije.*.vrednost' => ['required', 'string', 'max:255'],
            'akcije.*.tip' => ['required', Rule::enum(TipTransakcije::class)],
        ];
    }
}
