@extends('layouts.app')

@section('naslov', 'Registracija')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-4 text-center">Registracija</h1>

            <form method="POST" action="{{ route('registracija') }}" class="needs-validation" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="korisnicko_ime" class="form-label">Korisničko ime</label>
                    <input type="text" id="korisnicko_ime" name="korisnicko_ime" value="{{ old('korisnicko_ime') }}"
                           class="form-control @error('korisnicko_ime') is-invalid @enderror"
                           maxlength="50" autocomplete="username" required autofocus>
                    <div class="invalid-feedback">
                        @error('korisnicko_ime') {{ $message }} @else Dozvoljena su slova, brojevi, crtice i donje crte. @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           maxlength="100" autocomplete="email" required>
                    <div class="invalid-feedback">
                        @error('email') {{ $message }} @else Unesite ispravnu email adresu. @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="lozinka" class="form-label">Lozinka</label>
                    <div class="input-group has-validation">
                        <input type="password" id="lozinka" name="lozinka"
                               class="form-control @error('lozinka') is-invalid @enderror"
                               minlength="8" autocomplete="new-password" aria-describedby="lozinkaPomoc" required>
                        <button type="button" class="btn btn-outline-secondary" data-toggle-lozinka="lozinka">Prikaži</button>
                        <div class="invalid-feedback">
                            @error('lozinka') {{ $message }} @else Lozinka mora imati najmanje 8 karaktera. @enderror
                        </div>
                    </div>
                    <div id="lozinkaPomoc" class="form-text">Najmanje 8 karaktera, uključujući slovo i broj.</div>
                </div>

                <div class="mb-4">
                    <label for="lozinka_confirmation" class="form-label">Potvrda lozinke</label>
                    <input type="password" id="lozinka_confirmation" name="lozinka_confirmation"
                           class="form-control" data-poklapa-se-sa="lozinka" autocomplete="new-password" required>
                    <div class="invalid-feedback">Lozinke se ne poklapaju.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Registruj se</button>
            </form>

            <p class="text-center text-body-secondary mt-4 mb-0">
                Već imate nalog? <a href="{{ route('prijava') }}">Prijavite se</a>
            </p>
        </div>
    </div>
@endsection
