<?php

namespace App\Http\Requests\Porezi;

use App\Services\Uvoz\Rucni\PodesavanjaUvoza;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UvozRucniFormatRequest extends FormRequest
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
            'separator' => ['required', Rule::in(array_keys(PodesavanjaUvoza::SEPARATORI))],
            'kodna_strana' => ['required', Rule::in(array_keys(PodesavanjaUvoza::KODNE_STRANE))],
            'red_zaglavlja' => ['required', 'integer', 'min:1', 'max:100'],
            'decimalni_separator' => ['required', Rule::in(array_keys(PodesavanjaUvoza::DECIMALNI_SEPARATORI))],
            'separator_hiljada' => ['present', Rule::in(array_map('strval', array_keys(PodesavanjaUvoza::SEPARATORI_HILJADA))), 'different:decimalni_separator'],
        ];
    }

    /**
     * Forma šalje kodove za tab, razmak i "bez separatora" (PodesavanjaUvoza::kodZnaka()).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'separator' => PodesavanjaUvoza::znakIzKoda($this->input('separator')),
            'separator_hiljada' => PodesavanjaUvoza::znakIzKoda($this->input('separator_hiljada')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['separator_hiljada.different' => 'Separator hiljada mora se razlikovati od decimalnog separatora.'];
    }
}
