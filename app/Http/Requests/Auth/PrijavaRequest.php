<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrijavaRequest extends FormRequest
{
    private const MAKS_POKUSAJA = 5;

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
            'email' => ['required', 'string', 'email'],
            'lozinka' => ['required', 'string'],
        ];
    }

    /**
     * Pokušava prijavu uz ograničenje broja pokušaja po emailu i IP adresi.
     *
     * @throws ValidationException
     */
    public function prijavi(): void
    {
        $this->proveriOgranicenje();

        $kredencijali = [
            'email' => $this->string('email')->lower()->toString(),
            'password' => $this->string('lozinka')->toString(),
        ];

        if (! Auth::attempt($kredencijali, $this->boolean('zapamti'))) {
            RateLimiter::hit($this->kljucOgranicenja());

            // Ključ 'prijava', a ne 'email': ne zna se koje polje je pogrešno, pa se greška prikazuje ispod forme.
            throw ValidationException::withMessages([
                'prijava' => 'Pogrešan email ili lozinka.',
            ]);
        }

        RateLimiter::clear($this->kljucOgranicenja());
    }

    /**
     * @throws ValidationException
     */
    private function proveriOgranicenje(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kljucOgranicenja(), self::MAKS_POKUSAJA)) {
            return;
        }

        event(new Lockout($this));

        $sekunde = RateLimiter::availableIn($this->kljucOgranicenja());

        throw ValidationException::withMessages([
            'prijava' => "Previše pokušaja prijave. Pokušajte ponovo za {$sekunde} s.",
        ]);
    }

    private function kljucOgranicenja(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
