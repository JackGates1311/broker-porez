<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UvozTrading212Request extends FormRequest
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
            'fajlovi' => ['required', 'array', 'min:1', 'max:24'],
            'fajlovi.*' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
