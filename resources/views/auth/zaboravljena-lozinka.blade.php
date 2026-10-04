@extends('layouts.app')

@section('naslov', 'Zaboravljena lozinka')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Zaboravljena lozinka</h1>

            <p class="text-body-secondary text-center">
                Unesite email adresu naloga i poslaćemo vam link za postavljanje nove lozinke.
            </p>

            <form method="POST" action="{{ route('lozinka.zaboravljena') }}" class="needs-validation" novalidate>
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           maxlength="100" autocomplete="email" required autofocus>
                    <div class="invalid-feedback">
                        @error('email') {{ $message }} @else Unesite ispravnu email adresu. @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Pošalji link</button>
            </form>

            <p class="text-center text-body-secondary mt-4 mb-0">
                Setili ste se lozinke? <a href="{{ route('prijava') }}">Prijavite se</a>
            </p>
        </div>
    </div>
@endsection
