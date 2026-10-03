@extends('layouts.app')

@section('naslov', 'Poreski profil')

@section('sadrzaj')
    <div class="mx-auto" style="max-width: 720px">
        <h1 class="knjiga-naslov mb-1">Poreski profil</h1>
        <p class="text-body-secondary mb-4">
            Ovi podaci se upisuju u Deo 2 obrazaca PPDG-3R i PP OPO. Čuvaju se samo u vašem nalogu.
        </p>

        <form method="POST" action="{{ route('profil') }}" class="border bg-white p-4 needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="jmbg" class="form-label">JMBG</label>
                    <input id="jmbg" name="jmbg" value="{{ old('jmbg', $profil->jmbg) }}" inputmode="numeric" maxlength="13" pattern="\d{13}"
                           class="form-control @error('jmbg') is-invalid @enderror" autocomplete="off">
                    <div class="invalid-feedback">@error('jmbg') {{ $message }} @else JMBG ima 13 cifara. @enderror</div>
                </div>
                <div class="col-md-6">
                    <label for="ime_prezime" class="form-label">Ime i prezime</label>
                    <input id="ime_prezime" name="ime_prezime" value="{{ old('ime_prezime', $profil->ime_prezime) }}" maxlength="150"
                           class="form-control @error('ime_prezime') is-invalid @enderror" autocomplete="name">
                    <div class="invalid-feedback">@error('ime_prezime') {{ $message }} @enderror</div>
                </div>
                <div class="col-12">
                    <label for="adresa" class="form-label">Adresa</label>
                    <input id="adresa" name="adresa" value="{{ old('adresa', $profil->adresa) }}" maxlength="255"
                           class="form-control @error('adresa') is-invalid @enderror" autocomplete="street-address">
                    <div class="invalid-feedback">@error('adresa') {{ $message }} @enderror</div>
                </div>
                <div class="col-md-4">
                    <label for="prebivaliste_sifra" class="form-label">Šifra opštine prebivališta</label>
                    <input id="prebivaliste_sifra" name="prebivaliste_sifra" value="{{ old('prebivaliste_sifra', $profil->prebivaliste_sifra) }}"
                           inputmode="numeric" maxlength="3" pattern="\d{3}" aria-describedby="sifraPomoc"
                           class="form-control @error('prebivaliste_sifra') is-invalid @enderror">
                    <div id="sifraPomoc" class="form-text">Tri cifre, iz šifarnika opština Poreske uprave.</div>
                    <div class="invalid-feedback">@error('prebivaliste_sifra') {{ $message }} @else Unesite tri cifre. @enderror</div>
                </div>
                <div class="col-md-4">
                    <label for="telefon" class="form-label">Telefon</label>
                    <input id="telefon" name="telefon" value="{{ old('telefon', $profil->telefon) }}" maxlength="30" type="tel"
                           class="form-control @error('telefon') is-invalid @enderror" autocomplete="tel">
                    <div class="invalid-feedback">@error('telefon') {{ $message }} @enderror</div>
                </div>
                <div class="col-md-4">
                    <label for="zemlja_rezidentstva" class="form-label">Zemlja rezidentstva</label>
                    <input id="zemlja_rezidentstva" name="zemlja_rezidentstva" value="{{ old('zemlja_rezidentstva', $profil->zemlja_rezidentstva) }}" maxlength="3"
                           class="form-control @error('zemlja_rezidentstva') is-invalid @enderror">
                    <div class="invalid-feedback">@error('zemlja_rezidentstva') {{ $message }} @enderror</div>
                </div>
                <div class="col-12">
                    <label for="email" class="form-label">Email za obrazac</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $profil->email) }}" maxlength="100"
                           class="form-control @error('email') is-invalid @enderror" autocomplete="email">
                    <div class="invalid-feedback">@error('email') {{ $message }} @else Unesite ispravnu email adresu. @enderror</div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Sačuvaj profil</button>
                <a href="{{ route('pocetna') }}" class="btn btn-link">Nazad na pregled</a>
            </div>
        </form>
    </div>
@endsection
