<?php

namespace App\Http\Requests\Porezi;

use App\Services\Uvoz\Rucni\PodesavanjaUvoza;
use App\Services\Uvoz\Rucni\PoljeUvoza;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UvozRucniKoloneRequest extends FormRequest
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
        $pravila = [
            'kolone' => ['required', 'array'],
            'format_datuma' => ['required', Rule::in(array_keys(PodesavanjaUvoza::FORMATI_DATUMA))],
            'vremenska_zona' => ['required', Rule::in(array_keys(PodesavanjaUvoza::VREMENSKE_ZONE))],
        ];

        foreach (PoljeUvoza::cases() as $polje) {
            $pravila["kolone.{$polje->value}.kolona"] = ['nullable', 'string', 'max:255'];

            if ($polje->dozvoljavaKonstantu()) {
                $pravila["kolone.{$polje->value}.konstanta"] = ['nullable', 'string', 'regex:/^[A-Za-z]{3}$/'];
            }
        }

        return $pravila;
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ([PoljeUvoza::Vreme, PoljeUvoza::Akcija] as $polje) {
                if (blank($this->input("kolone.{$polje->value}.kolona"))) {
                    $validator->errors()->add("kolone.{$polje->value}.kolona", "Izaberite kolonu za polje „{$polje->naziv()}”.");
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $nazivi = [];

        foreach (PoljeUvoza::cases() as $polje) {
            $nazivi["kolone.{$polje->value}.kolona"] = mb_strtolower($polje->naziv());
            $nazivi["kolone.{$polje->value}.konstanta"] = mb_strtolower($polje->naziv());
        }

        return $nazivi;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['kolone.*.konstanta.regex' => 'Valuta se piše kao troslovna ISO oznaka, npr. USD ili EUR.'];
    }
}
