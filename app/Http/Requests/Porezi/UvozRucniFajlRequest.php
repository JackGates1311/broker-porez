<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UvozRucniFajlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Šablon se proverava u kontroleru (findOrFail nad šablonima korisnika).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fajl_rucni' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'sablon_id' => ['nullable', 'integer'],
        ];
    }
}
