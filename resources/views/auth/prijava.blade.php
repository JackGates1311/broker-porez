@extends('layouts.app')

@section('naslov', 'Prijava')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-4 text-center">Prijava</h1>

            <form method="POST" action="{{ route('prijava') }}" class="needs-validation" novalidate data-samo-greske>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           autocomplete="email" required autofocus>
                    <div class="invalid-feedback">
                        @error('email') {{ $message }} @else Unesite ispravnu email adresu. @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="lozinka" class="form-label">Lozinka</label>
                    <div class="input-group has-validation">
                        <input type="password" id="lozinka" name="lozinka"
                               class="form-control @error('lozinka') is-invalid @enderror"
                               autocomplete="current-password" required>
                        <button type="button" class="btn btn-outline-secondary" data-toggle-lozinka="lozinka">Prikaži</button>
                        <div class="invalid-feedback">
                            @error('lozinka') {{ $message }} @else Unesite lozinku. @enderror
                        </div>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" id="zapamti" name="zapamti" value="1" class="form-check-input" @checked(old('zapamti'))>
                    <label for="zapamti" class="form-check-label">Zapamti me</label>
                </div>

                @error('prijava')
                    <div class="alert alert-danger py-2 small" role="alert">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn-primary w-100">Prijavi se</button>
            </form>

            <p class="text-center text-body-secondary mt-4 mb-0">
                Nemate nalog? <a href="{{ route('registracija') }}">Registrujte se</a>
            </p>
            <p class="text-center mt-2 mb-0">
                <a href="{{ route('lozinka.zaboravljena') }}">Zaboravljena lozinka?</a>
            </p>
        </div>
    </div>
@endsection
