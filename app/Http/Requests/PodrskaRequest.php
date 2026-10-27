<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * Poruka iz widgeta za povratne informacije. Ista ograničenja proverava i resources/js/podrska.ts.
 */
class PodrskaRequest extends FormRequest
{
    public const MAKS_PRILOGA = 5;

    public const MAKS_KB_PO_PRILOGU = 5120;

    public const MAKS_KB_UKUPNO = 10240;

    public const DOZVOLJENI_TIPOVI = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Naslov ide u Subject mejla: bez prelaza u novi red.
        if (is_string($this->input('naslov'))) {
            $this->merge(['naslov' => trim(preg_replace('/\s+/u', ' ', $this->input('naslov')) ?? '')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'naslov' => ['nullable', 'string', 'max:150'],
            'poruka' => ['required', 'string', 'max:5000'],
            'prilozi' => ['nullable', 'array', 'max:'.self::MAKS_PRILOGA],
            'prilozi.*' => ['file', 'mimes:'.implode(',', self::DOZVOLJENI_TIPOVI), 'max:'.self::MAKS_KB_PO_PRILOGU],
            'stranica' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $ukupno = collect($this->file('prilozi', []))
                ->filter(fn ($fajl) => $fajl instanceof UploadedFile)
                ->sum(fn (UploadedFile $fajl) => $fajl->getSize());

            if ($ukupno > self::MAKS_KB_UKUPNO * 1024) {
                $validator->errors()->add('prilozi', 'Prilozi zajedno ne smeju biti veći od '.(self::MAKS_KB_UKUPNO / 1024).' MB.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prilozi.*.mimes' => 'Dozvoljene su slike (JPG, PNG, WEBP), PDF i TXT fajlovi.',
            'prilozi.*.max' => 'Fajl je veći od '.(self::MAKS_KB_PO_PRILOGU / 1024).' MB.',
        ];
    }
}
