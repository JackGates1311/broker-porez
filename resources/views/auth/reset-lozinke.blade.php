@extends('layouts.app')

@section('naslov', 'Nova lozinka')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-4 text-center">Nova lozinka</h1>

            @if (! $linkVazi || $errors->has('token'))
                <div class="alert alert-danger">
                    {{ $errors->first('token') ?: 'Link za resetovanje nije ispravan ili je istekao.' }}
                </div>

                <a href="{{ route('lozinka.zaboravljena') }}" class="btn btn-primary w-100">Zatražite novi link</a>
            @else
                <form method="POST" action="{{ route('lozinka.reset.sacuvaj') }}" class="needs-validation" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="lozinka" class="form-label">Nova lozinka</label>
                        <div class="input-group has-validation">
                            <input type="password" id="lozinka" name="lozinka"
                                   class="form-control @error('lozinka') is-invalid @enderror"
                                   minlength="8" autocomplete="new-password" aria-describedby="lozinkaPomoc" required autofocus>
                            <button type="button" class="btn btn-outline-secondary" data-toggle-lozinka="lozinka">Prikaži</button>
                            <div class="invalid-feedback">
                                @error('lozinka') {{ $message }} @else Lozinka mora imati najmanje 8 karaktera. @enderror
                            </div>
                        </div>
                        <div id="lozinkaPomoc" class="form-text">Najmanje 8 karaktera, uključujući slovo i broj.</div>
                    </div>

                    <div class="mb-4">
                        <label for="lozinka_confirmation" class="form-label">Potvrda nove lozinke</label>
                        <input type="password" id="lozinka_confirmation" name="lozinka_confirmation"
                               class="form-control" data-poklapa-se-sa="lozinka" autocomplete="new-password" required>
                        <div class="invalid-feedback">Lozinke se ne poklapaju.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Sačuvaj novu lozinku</button>
                </form>
            @endif

            <p class="text-center text-body-secondary mt-4 mb-0">
                <a href="{{ route('prijava') }}">Nazad na prijavu</a>
            </p>
        </div>
    </div>
@endsection
