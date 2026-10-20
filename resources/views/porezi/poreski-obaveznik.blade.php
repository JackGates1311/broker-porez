@extends('layouts.knjiga')

@use('App\Models\PoreskiObaveznik')
@use('App\Support\Drzave')
@use('App\Support\Opstine')
@use('App\Support\TekuciRacun')

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

        @if ($popunjen && ! $prihvatioUslove && $ranijePrihvatio)
            <div class="alert alert-info" role="alert">
                Uslovi korišćenja i Politika privatnosti su ažurirani. Pročitajte ih i potvrdite na dnu forme da biste nastavili.
            </div>
        @endif

        <p class="text-body-secondary mb-4">
            Deo 2 obrasca PPDG-3R: podaci o poreskom obavezniku, uz tekući račun i broj pasoša za PP OPO.
            Obavezni su samo tip obaveznika, ime i prezime, opština, email i zemlja rezidentstva; ostala polja su opciona.
            Podaci se čuvaju samo u vašem nalogu, na serveru u EU; JMBG, tekući račun i broj pasoša su u bazi šifrovani.
        </p>

        @if ($popunjen && $nedostajeZaXml !== [])
            <div class="alert alert-warning" role="alert">
                <p class="mb-1">Niste uneli: {{ implode(', ', $nedostajeZaXml) }}. Posledice:</p>
                <ul class="mb-0">
                    <li>PDF obrasci se preuzimaju sa praznim poljima, koja pre podnošenja dopisujete rukom.</li>
                    <li>XML za „XML unos” na portalu ePorezi se ne pravi, jer ga portal bez tih podataka ne prima. Prijavu tada unosite ručno na portalu.</li>
                </ul>
            </div>
        @endif

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
                    <label for="pib" class="form-label">2.2 JMBG/EBS/PIB <span class="text-body-secondary">(opciono)</span></label>
                    <input id="pib" name="pib" value="{{ old('pib', $obaveznik->pib) }}" inputmode="numeric" maxlength="13"
                           pattern="\d{9}|\d{13}" autocomplete="off" aria-describedby="pibPomoc"
                           class="form-control @error('pib') is-invalid @enderror">
                    <div id="pibPomoc" class="form-text">Poreski identifikacioni broj. Bez njega PDF ostaje sa praznim poljem, a XML za ePorezi se ne pravi.</div>
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
                    <label for="prebivaliste" class="form-label">2.4 Prebivalište/boravište/sedište/opština ostvarivanja prihoda <span class="text-body-secondary">(opciono)</span></label>
                    <input id="prebivaliste" name="prebivaliste" value="{{ old('prebivaliste', $obaveznik->prebivaliste) }}" maxlength="255"
                           aria-describedby="prebivalistePomoc"
                           class="form-control @error('prebivaliste') is-invalid @enderror">
                    <div id="prebivalistePomoc" class="form-text">Ispisuje se samo na PDF-u PPDG-3R; bez njega polje 2.4 ostaje prazno. XML koristi opštinu ispod.</div>
                    <div class="invalid-feedback">@error('prebivaliste') {{ $message }} @else Unesite prebivalište/boravište. @enderror</div>
                </div>

                <div class="col-12">
                    <label for="opstina_sifra" class="form-label">Opština prebivališta (šifra za PP OPO 2.4)</label>
                    <input id="opstina_sifra" name="opstina_sifra"
                           value="{{ Opstine::prikaz(old('opstina_sifra', $obaveznik->opstina_sifra)) }}"
                           list="opstine" maxlength="100" autocomplete="off" placeholder="Počnite da kucate naziv opštine"
                           required aria-describedby="opstinaPomoc"
                           class="form-control @error('opstina_sifra') is-invalid @enderror">
                    <datalist id="opstine">
                        @foreach (Opstine::sve() as $sifra => $naziv)
                            <option value="{{ $naziv }} ({{ $sifra }})"></option>
                        @endforeach
                    </datalist>
                    <div id="opstinaPomoc" class="form-text">Opština ili grad prebivališta, npr. Grad Novi Sad (223). Za nerezidenta opština boravišta, odnosno sedišta poreskog punomoćnika.</div>
                    <div class="invalid-feedback">@error('opstina_sifra') {{ $message }} @else Izaberite opštinu sa liste. @enderror</div>
                </div>

                <div class="col-12">
                    <label for="adresa" class="form-label">2.5 Adresa poreskog obaveznika <span class="text-body-secondary">(opciono)</span></label>
                    <input id="adresa" name="adresa" value="{{ old('adresa', $obaveznik->adresa) }}" maxlength="255"
                           autocomplete="street-address" aria-describedby="adresaPomoc"
                           class="form-control @error('adresa') is-invalid @enderror">
                    <div id="adresaPomoc" class="form-text">Bez nje polje 2.5 ostaje prazno na PDF-u, a izostavlja se iz XML-a.</div>
                    <div class="invalid-feedback">@error('adresa') {{ $message }} @else Unesite adresu. @enderror</div>
                </div>

                <div class="col-lg-6">
                    <label for="telefon" class="form-label">2.6 Broj telefona podnosioca prijave <span class="text-body-secondary">(opciono)</span></label>
                    <input id="telefon" name="telefon" value="{{ old('telefon', $obaveznik->telefon) }}" maxlength="30" type="tel"
                           pattern="\+?(?=(?:[^0-9]*[0-9]){6,15}[^0-9]*$)[0-9 \(\)\/.\-]+" placeholder="npr. +381 64 123 4567"
                           autocomplete="tel" aria-describedby="telefonPomoc"
                           class="form-control @error('telefon') is-invalid @enderror">
                    <div id="telefonPomoc" class="form-text">Služi Poreskoj upravi za kontakt. Bez njega polje 2.6 ostaje prazno.</div>
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
                    <label for="jmbg_podnosioca" class="form-label">2.8 JMBG podnosioca prijave <span class="text-body-secondary">(opciono)</span></label>
                    <input id="jmbg_podnosioca" name="jmbg_podnosioca"
                           value="{{ old('jmbg_podnosioca', $obaveznik->jmbg_podnosioca ?? (strlen((string) $obaveznik->pib) === 13 ? $obaveznik->pib : null)) }}"
                           inputmode="numeric" maxlength="13" pattern="\d{13}" autocomplete="off" aria-describedby="jmbgPodnosiocaPomoc"
                           class="form-control @error('jmbg_podnosioca') is-invalid @enderror">
                    <div id="jmbgPodnosiocaPomoc" class="form-text">Bez njega PDF ostaje sa praznim poljem, a XML za ePorezi se ne pravi.</div>
                    <div class="invalid-feedback">@error('jmbg_podnosioca') {{ $message }} @else JMBG ima 13 cifara. @enderror</div>
                </div>

                {{-- 2.9 se popunjava za svaki tip; rezidentu je unapred upisana Srbija. --}}
                <div class="col-lg-6">
                    <label for="zemlja_rezidentstva" class="form-label">2.9 Zemlja rezidentstva</label>
                    <input id="zemlja_rezidentstva" name="zemlja_rezidentstva"
                           value="{{ Drzave::prikaz(old('zemlja_rezidentstva', $obaveznik->zemlja_rezidentstva ?? (in_array($tip, [3, 4], true) ? null : 'RS'))) }}"
                           list="drzave" maxlength="100" autocomplete="off" placeholder="Počnite da kucate naziv države"
                           required aria-describedby="zemljaPomoc"
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

                <div class="col-lg-6">
                    <label for="tekuci_racun" class="form-label">Tekući račun (PP OPO 3.2) <span class="text-body-secondary">(opciono)</span></label>
                    <input id="tekuci_racun" name="tekuci_racun" value="{{ TekuciRacun::prikaz(old('tekuci_racun', $obaveznik->tekuci_racun)) }}"
                           maxlength="22" pattern="\s*(\d{3}-\d{1,13}-\d{2}|\d{18})\s*" placeholder="npr. 265-0000000360051-46"
                           autocomplete="off" aria-describedby="racunPomoc"
                           class="form-control @error('tekuci_racun') is-invalid @enderror">
                    <div id="racunPomoc" class="form-text">
                        Račun na koji primate prihod. Skraćen zapis (265-360051-46) se dopunjuje nulama.
                        Bez njega polje 3.2 na PP OPO ostaje prazno.
                    </div>
                    <div class="invalid-feedback">@error('tekuci_racun') {{ $message }} @else Upišite račun kao 265-0000000360051-46 ili 18 cifara. @enderror</div>
                </div>
                <div class="col-lg-6">
                    <label for="broj_pasosa" class="form-label">Broj pasoša (PP OPO 2.9) <span class="text-body-secondary">(opciono)</span></label>
                    <input id="broj_pasosa" name="broj_pasosa" value="{{ old('broj_pasosa', $obaveznik->broj_pasosa) }}"
                           maxlength="30" pattern="\s*[A-Za-z0-9]+\s*" autocomplete="off" aria-describedby="pasosPomoc"
                           class="form-control @error('broj_pasosa') is-invalid @enderror">
                    <div id="pasosPomoc" class="form-text">Upisuje se ako ga imate; nerezidenti ga po pravilu navode.</div>
                    <div class="invalid-feedback">@error('broj_pasosa') {{ $message }} @else Broj pasoša sme da sadrži samo slova i cifre. @enderror</div>
                </div>
            </div>

            @unless ($prihvatioUslove)
                <div class="prihvatanje-uslova mt-4">
                    <div class="form-check">
                        <input type="checkbox" id="prihvatam_uslove" name="prihvatam_uslove" value="1" required @checked(old('prihvatam_uslove'))
                               class="form-check-input @error('prihvatam_uslove') is-invalid @enderror">
                        <label for="prihvatam_uslove" class="form-check-label">
                            Pročitao/la sam i prihvatam
                            {{-- Sa JavaScript-om tekst se otvara u modalu, bez njega na svojoj strani u novom tabu. --}}
                            <a href="{{ route('pravno.uslovi') }}" target="_blank" rel="noopener" data-bs-toggle="modal" data-bs-target="#modalUslovi">Uslove korišćenja</a> i
                            <a href="{{ route('pravno.politika') }}" target="_blank" rel="noopener" data-bs-toggle="modal" data-bs-target="#modalPolitika">Politiku privatnosti</a>.
                        </label>
                        <div class="invalid-feedback">@error('prihvatam_uslove') {{ $message }} @else Potvrdite da prihvatate uslove. @enderror</div>
                    </div>
                    <p class="small text-body-secondary mb-0 mt-2">
                        BrokerPorez je pomoćni alat: obračun zavisi od podataka koje uvezete, a prijavu pre podnošenja proveravate i podnosite vi.
                    </p>
                </div>
            @endunless

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Sačuvaj</button>
            </div>
        </form>

        @unless ($prihvatioUslove)
            @foreach ([
                'modalUslovi' => ['Uslovi korišćenja', 'pravno._uslovi', 'pravno.uslovi'],
                'modalPolitika' => ['Politika privatnosti', 'pravno._politika', 'pravno.politika'],
            ] as $id => [$naslov, $tekst, $ruta])
                <div class="modal fade pravni-modal" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Naslov" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="me-3">
                                    <h2 class="modal-title h5" id="{{ $id }}Naslov">{{ $naslov }}</h2>
                                    <div class="small text-body-secondary">Važi od {{ \App\Support\Pravno::vaziOdPrikaz() }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                            </div>
                            {{-- Telo modala ide od ivice do ivice (skrol uz desnu ivicu), a širinu teksta ograničava unutrašnji blok. --}}
                            <div class="modal-body">
                                <div class="pravni-tekst">
                                    @include($tekst)
                                </div>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <a href="{{ route($ruta) }}" target="_blank" rel="noopener" class="small">Otvori na posebnoj strani</a>
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Zatvori</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endunless
    </div>
@endsection
