@extends('layouts.knjiga')

@use('App\Models\PoreskiObaveznik')
@use('App\Support\Drzave')

@section('naslov', 'Poreski obaveznik')

@section('knjiga')
    @php($tip = (int) old('tip_obaveznika', $obaveznik->tip_obaveznika))

    <div style="max-width: 880px">
        @unless ($popunjen)
            <div class="alert alert-info" role="alert">
                Pre korišćenja poreske knjige popunite podatke o poreskom obavezniku.
                Upisuju se u Deo 2 obrazaca PPDG-3R i PP OPO.
            </div>
        @endunless

        <p class="text-body-secondary mb-4">
            Deo 2 obrasca PPDG-3R: podaci o poreskom obavezniku. Sva polja su obavezna osim 2.10.
            Podaci se čuvaju samo u vašem nalogu.
        </p>

        <form method="POST" action="{{ route('poreski-obaveznik') }}" class="border bg-white p-4 needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12">
                    <label for="tip_obaveznika" class="form-label">2.1 Tip poreskog obaveznika</label>
                    <select id="tip_obaveznika" name="tip_obaveznika" required data-tip-obaveznika
                            class="form-select @error('tip_obaveznika') is-invalid @enderror">
                        @foreach (PoreskiObaveznik::TIPOVI as $oznaka => $naziv)
                            <option value="{{ $oznaka }}" @selected($tip === $oznaka)>({{ $oznaka }}) {{ $naziv }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">@error('tip_obaveznika') {{ $message }} @else Izaberite tip obaveznika. @enderror</div>
                </div>

                <div class="col-lg-6">
                    <label for="pib" class="form-label">2.2 Poreski identifikacioni broj (JMBG/EBS/PIB)</label>
                    <input id="pib" name="pib" value="{{ old('pib', $obaveznik->pib) }}" inputmode="numeric" maxlength="13"
                           pattern="\d{9}|\d{13}" required autocomplete="off"
                           class="form-control @error('pib') is-invalid @enderror">
                    <div class="invalid-feedback">@error('pib') {{ $message }} @else JMBG i EBS imaju 13 cifara, PIB 9. @enderror</div>
                </div>
                <div class="col-lg-6">
                    <label for="ime_prezime" class="form-label">2.3 Ime i prezime poreskog obaveznika</label>
                    <input id="ime_prezime" name="ime_prezime" value="{{ old('ime_prezime', $obaveznik->ime_prezime) }}" maxlength="150"
                           required autocomplete="name"
                           class="form-control @error('ime_prezime') is-invalid @enderror">
                    <div class="invalid-feedback">@error('ime_prezime') {{ $message }} @else Unesite ime i prezime. @enderror</div>
                </div>

                <div class="col-12">
                    <label for="prebivaliste" class="form-label">2.4 Prebivalište/boravište/sedište/opština ostvarivanja prihoda</label>
                    <input id="prebivaliste" name="prebivaliste" value="{{ old('prebivaliste', $obaveznik->prebivaliste) }}" maxlength="255"
                           required
                           class="form-control @error('prebivaliste') is-invalid @enderror">
                    <div class="invalid-feedback">@error('prebivaliste') {{ $message }} @else Unesite prebivalište/boravište. @enderror</div>
                </div>

                <div class="col-12">
                    <label for="adresa" class="form-label">2.5 Adresa poreskog obaveznika</label>
                    <input id="adresa" name="adresa" value="{{ old('adresa', $obaveznik->adresa) }}" maxlength="255"
                           required autocomplete="street-address"
                           class="form-control @error('adresa') is-invalid @enderror">
                    <div class="invalid-feedback">@error('adresa') {{ $message }} @else Unesite adresu. @enderror</div>
                </div>

                <div class="col-lg-6">
                    <label for="telefon" class="form-label">2.6 Broj telefona podnosioca prijave</label>
                    <input id="telefon" name="telefon" value="{{ old('telefon', $obaveznik->telefon) }}" maxlength="30" type="tel"
                           pattern="\+?(?=(?:[^0-9]*[0-9]){6,15}[^0-9]*$)[0-9 \(\)\/.\-]+" placeholder="npr. +381 64 123 4567"
                           required autocomplete="tel"
                           class="form-control @error('telefon') is-invalid @enderror">
                    <div class="invalid-feedback">@error('telefon') {{ $message }} @else Unesite broj telefona: samo cifre, razmaci i znakovi + - / ( ), najmanje 6 cifara. @enderror</div>
                </div>
                <div class="col-lg-6">
                    <label for="email" class="form-label">2.7 Elektronska adresa</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $obaveznik->email) }}" maxlength="100"
                           pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" placeholder="npr. ime@primer.rs"
                           required autocomplete="email" aria-describedby="emailPomoc"
                           class="form-control @error('email') is-invalid @enderror">
                    <div id="emailPomoc" class="form-text">Na nju stižu obaveštenja i poreski akti u vezi sa prijavom.</div>
                    <div class="invalid-feedback">@error('email') {{ $message }} @else Unesite ispravnu email adresu, npr. ime@primer.rs. @enderror</div>
                </div>

                <div class="col-lg-6">
                    <label for="jmbg_podnosioca" class="form-label">2.8 JMBG podnosioca prijave</label>
                    <input id="jmbg_podnosioca" name="jmbg_podnosioca"
                           value="{{ old('jmbg_podnosioca', $obaveznik->jmbg_podnosioca ?? (strlen((string) $obaveznik->pib) === 13 ? $obaveznik->pib : null)) }}"
                           inputmode="numeric" maxlength="13" pattern="\d{13}" required autocomplete="off"
                           class="form-control @error('jmbg_podnosioca') is-invalid @enderror">
                    <div class="invalid-feedback">@error('jmbg_podnosioca') {{ $message }} @else JMBG ima 13 cifara. @enderror</div>
                </div>

                <div data-prikazi-za-tip="3,4" @class(['col-lg-6', 'd-none' => ! in_array($tip, [3, 4], true)])>
                    <label for="zemlja_rezidentstva" class="form-label">2.9 Zemlja rezidentstva</label>
                    <input id="zemlja_rezidentstva" name="zemlja_rezidentstva"
                           value="{{ Drzave::prikaz(old('zemlja_rezidentstva', $obaveznik->zemlja_rezidentstva)) }}"
                           list="drzave" maxlength="100" autocomplete="off" placeholder="Počnite da kucate naziv države"
                           required @disabled(! in_array($tip, [3, 4], true)) aria-describedby="zemljaPomoc"
                           class="form-control @error('zemlja_rezidentstva') is-invalid @enderror">
                    <datalist id="drzave">
                        @foreach (Drzave::sve() as $oznaka => $naziv)
                            <option value="{{ $naziv }} ({{ $oznaka }})"></option>
                        @endforeach
                    </datalist>
                    <div id="zemljaPomoc" class="form-text">Izaberite državu sa liste, npr. Srbija (RS).</div>
                    <div class="invalid-feedback">@error('zemlja_rezidentstva') {{ $message }} @else Izaberite državu sa liste. @enderror</div>
                </div>

                <div data-prikazi-za-tip="4" @class(['col-lg-6', 'd-none' => $tip !== 4])>
                    <label for="pib_punomocnika" class="form-label">2.10 JMBG/PIB poreskog punomoćnika <span class="text-body-secondary">(opciono)</span></label>
                    <input id="pib_punomocnika" name="pib_punomocnika" value="{{ old('pib_punomocnika', $obaveznik->pib_punomocnika) }}"
                           inputmode="numeric" maxlength="13" pattern="\d{9}|\d{13}" autocomplete="off" @disabled($tip !== 4)
                           class="form-control @error('pib_punomocnika') is-invalid @enderror">
                    <div class="invalid-feedback">@error('pib_punomocnika') {{ $message }} @else JMBG ima 13 cifara, PIB 9. @enderror</div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Sačuvaj</button>
            </div>
        </form>
    </div>
@endsection
