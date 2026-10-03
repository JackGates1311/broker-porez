@extends('layouts.app')

@section('naslov', 'Potvrda email adrese')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Potvrdite email adresu</h1>

            <p class="text-body-secondary text-center">
                Poslali smo 6-cifreni kod na <strong>{{ $email }}</strong>.
                Kod važi {{ \App\Services\VerifikacijaEmailaService::TRAJANJE_KODA_MINUTA }} minuta.
            </p>

            <form method="POST" action="{{ route('verifikacija') }}" class="needs-validation" novalidate data-kod-forma>
                @csrf

                <div class="mb-4">
                    <label for="kod" class="form-label visually-hidden">Verifikacioni kod</label>
                    <input type="text" id="kod" name="kod" value="{{ old('kod') }}"
                           class="form-control form-control-lg text-center kod-unos @error('kod') is-invalid @enderror"
                           inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6"
                           placeholder="••••••" required autofocus data-kod-unos>
                    <div class="invalid-feedback text-center">
                        @error('kod') {{ $message }} @else Unesite 6 cifara. @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Potvrdi</button>
            </form>

            <hr class="my-4">

            <form method="POST" action="{{ route('verifikacija.ponovo') }}" class="text-center">
                @csrf
                <span class="text-body-secondary">Niste dobili kod?</span>
                <button type="submit" class="btn btn-link p-0 align-baseline"
                        @if (session('status')) data-odbrojavanje="60" @endif>
                    Pošalji ponovo
                </button>
            </form>

            @error('throttle')
                <div class="text-danger small text-center mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>
@endsection
