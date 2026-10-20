@extends('layouts.app')

@section('naslov', 'Moj nalog')
@section('sirina-poruke', 'mx-auto nalog-strana')

@section('sadrzaj')
    <div class="nalog-strana mx-auto">
        <h1 class="knjiga-naslov mb-4">Moj nalog</h1>

        <section class="border bg-white p-4 mb-4" aria-labelledby="podaciNaloga">
            <h2 id="podaciNaloga" class="h5 mb-3">Podaci naloga</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5 fw-normal text-body-secondary">Email</dt>
                <dd class="col-sm-7 text-break">{{ $korisnik->email }}</dd>
                <dt class="col-sm-5 fw-normal text-body-secondary">Prijava</dt>
                <dd class="col-sm-7">{{ $korisnik->google_id !== null ? 'Google nalog ili lozinka' : 'Lozinka' }}</dd>
                <dt class="col-sm-5 fw-normal text-body-secondary">Registrovan</dt>
                <dd class="col-sm-7">{{ $korisnik->kreirano_at?->timezone('Europe/Belgrade')->format('d.m.Y.') }}</dd>
                <dt class="col-sm-5 fw-normal text-body-secondary">Uslovi prihvaćeni</dt>
                <dd class="col-sm-7 mb-0">
                    @if ($korisnik->uslovi_prihvaceni_at)
                        {{ $korisnik->uslovi_prihvaceni_at->timezone('Europe/Belgrade')->format('d.m.Y. H:i') }} (verzija {{ $korisnik->uslovi_verzija }})
                    @else
                        Još niste prihvatili uslove.
                    @endif
                </dd>
            </dl>
        </section>

        <section class="border bg-white p-4 mb-4" aria-labelledby="korisnickoImeNaslov">
            <h2 id="korisnickoImeNaslov" class="h5 mb-3">Korisničko ime</h2>
            <form method="POST" action="{{ route('nalog.korisnicko-ime') }}" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="mb-3">
                    @include('auth._korisnicko-ime', ['vrednost' => $korisnik->korisnicko_ime])
                </div>
                <button type="submit" class="btn btn-outline-primary">Sačuvaj ime</button>
            </form>
        </section>

        <section class="border bg-white p-4 mb-4" aria-labelledby="dvaKoraka">
            <h2 id="dvaKoraka" class="h5 mb-2 d-flex align-items-center gap-2">
                Prijava u dva koraka
                <span class="badge rounded-pill text-bg-light border fw-normal">Uskoro</span>
            </h2>
            <p class="text-body-secondary">
                Pored lozinke, pri prijavi ćete unositi i šestocifreni kod iz aplikacije za autentifikaciju
                (Google Authenticator, Microsoft Authenticator, Aegis i slične, standard TOTP).
                Tako niko ne može da uđe u nalog samo sa vašom lozinkom.
            </p>
            <button type="button" class="btn btn-outline-secondary" disabled aria-disabled="true">Uključi prijavu u dva koraka</button>
        </section>

        <section class="border bg-white p-4 mb-4" aria-labelledby="mojiPodaci">
            <h2 id="mojiPodaci" class="h5 mb-2">Moji podaci</h2>
            <p class="text-body-secondary">
                Preuzmite sve što čuvamo o vama: nalog, podatke obaveznika, transakcije i šablone uvoza, u JSON fajlu.
                Podaci se čuvaju na serveru u EU, ne prodajemo ih i ne delimo radi oglašavanja.
                Detalji su u <a href="{{ route('pravno.politika') }}">Politici privatnosti</a>.
            </p>
            <a href="{{ route('nalog.izvoz') }}" class="btn btn-outline-primary">Preuzmi moje podatke</a>
        </section>

        <section class="opasna-zona" aria-labelledby="brisanjeNaloga">
            <h2 id="brisanjeNaloga" class="h5 mb-2 text-danger">Brisanje naloga</h2>
            <p>
                Brisanje je trajno: uklanjaju se nalog, podaci obaveznika, sve transakcije, obračuni i šabloni, i ne mogu se vratiti.
                Pre brisanja preuzmite PDF/XML obrasce i svoje podatke, jer dokumentaciju za poresku prijavu čuvate vi.
            </p>

            <form method="POST" action="{{ route('nalog.obrisi') }}" class="needs-validation" novalidate
                  data-potvrda="Trajno obrisati nalog i sve podatke? Ovo ne može da se poništi.">
                @csrf
                @method('DELETE')

                <div class="mb-3">
                    <label for="potvrda_emaila" class="form-label">Za potvrdu upišite email adresu naloga</label>
                    <input type="email" id="potvrda_emaila" name="potvrda_emaila" required autocomplete="off"
                           placeholder="{{ $korisnik->email }}"
                           class="form-control @error('potvrda_emaila') is-invalid @enderror">
                    <div class="invalid-feedback">@error('potvrda_emaila') {{ $message }} @else Upišite email adresu naloga. @enderror</div>
                </div>

                <button type="submit" class="btn btn-danger">Trajno obriši nalog</button>
            </form>
        </section>
    </div>
@endsection
