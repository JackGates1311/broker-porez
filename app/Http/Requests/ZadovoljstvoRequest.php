<?php

namespace App\Http\Requests;

use App\Models\OcenaZadovoljstva;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Odgovor na pitanje o zadovoljstvu: ocena (posalji), „Ne sada” (kasnije) ili „Ne pitaj više” (nikad).
 */
class ZadovoljstvoRequest extends FormRequest
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
            'akcija' => ['required', Rule::in(['posalji', 'kasnije', 'nikad'])],
            'ocena' => ['exclude_unless:akcija,posalji', 'required', 'integer', 'between:1,5'],
            'komentar' => ['exclude_unless:akcija,posalji', 'nullable', 'string', 'max:2000'],
            'povod' => ['exclude_unless:akcija,posalji', 'required', Rule::in(array_keys(OcenaZadovoljstva::POVODI))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ocena.required' => 'Izaberite ocenu od 1 do 5.',
            'ocena.between' => 'Izaberite ocenu od 1 do 5.',
        ];
    }
}
