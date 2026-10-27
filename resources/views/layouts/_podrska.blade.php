{{-- Widget za povratne informacije: samo za verifikovane insajdere (korisnici.is_insajder). Slanje: resources/js/podrska.ts. --}}
@php($greskePodrske = $errors->hasAny(['naslov', 'poruka', 'prilozi', 'prilozi.*']))

<button type="button" class="btn btn-primary podrska-dugme" data-bs-toggle="modal" data-bs-target="#podrskaModal"
        aria-label="Pošalji povratnu informaciju">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
        <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2"/>
    </svg>
    <span class="d-none d-sm-inline">Povratna informacija</span>
</button>

<div class="modal fade" id="podrskaModal" tabindex="-1" aria-labelledby="podrskaNaslov" aria-hidden="true"
     @if ($greskePodrske) data-podrska-otvori @endif>
    <div class="modal-dialog modal-dialog-scrollable">
        <form method="POST" action="{{ route('podrska.posalji') }}" enctype="multipart/form-data"
              class="modal-content needs-validation" novalidate data-samo-greske data-podrska>
            @csrf
            <input type="hidden" name="stranica" value="{{ request()->fullUrl() }}">

            <div class="modal-header">
                <h2 class="modal-title h5" id="podrskaNaslov">Povratna informacija</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>

            <div class="modal-body">
                <p class="small text-body-secondary">
                    Javite nam grešku, nejasnoću ili ideju.
                </p>

                <p class="small text-body-secondary">
                    Odgovor će biti dostavljen na: <b>{{ auth()->user()->email }}</b>
                </p>

                <div class="mb-3">
                    <label for="podrskaNaslovPolje" class="form-label">Naslov <span class="text-body-secondary">(nije obavezno)</span></label>
                    <input type="text" id="podrskaNaslovPolje" name="naslov" maxlength="150" value="{{ old('naslov') }}"
                           class="form-control @error('naslov') is-invalid @enderror">
                    <div class="invalid-feedback" data-greska-za="naslov">@error('naslov') {{ $message }} @enderror</div>
                </div>

                <div class="mb-3">
                    <label for="podrskaPoruka" class="form-label">Poruka</label>
                    <textarea id="podrskaPoruka" name="poruka" rows="5" maxlength="5000" required
                              class="form-control @error('poruka') is-invalid @enderror">{{ old('poruka') }}</textarea>
                    <div class="invalid-feedback" data-greska-za="poruka">@error('poruka') {{ $message }} @else Upišite poruku. @enderror</div>
                </div>

                <div class="mb-3">
                    <label for="podrskaPrilozi" class="form-label">Prilozi <span class="text-body-secondary">(nije obavezno)</span></label>
                    <input type="file" id="podrskaPrilozi" name="prilozi[]" multiple
                           accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,image/jpeg,image/png,image/webp,application/pdf,text/plain"
                           class="form-control @if ($errors->hasAny(['prilozi', 'prilozi.*'])) is-invalid @endif"
                           data-maks-priloga="{{ \App\Http\Requests\PodrskaRequest::MAKS_PRILOGA }}"
                           data-maks-kb="{{ \App\Http\Requests\PodrskaRequest::MAKS_KB_PO_PRILOGU }}"
                           data-maks-kb-ukupno="{{ \App\Http\Requests\PodrskaRequest::MAKS_KB_UKUPNO }}"
                           data-tipovi="{{ implode(',', \App\Http\Requests\PodrskaRequest::DOZVOLJENI_TIPOVI) }}">
                    <div class="invalid-feedback" data-greska-za="prilozi">{{ $errors->first('prilozi') ?: $errors->first('prilozi.*') }}</div>
                    <div class="form-text">
                        Do {{ \App\Http\Requests\PodrskaRequest::MAKS_PRILOGA }} fajlova: slike (JPG, PNG, WEBP), PDF ili TXT,
                        najviše {{ \App\Http\Requests\PodrskaRequest::MAKS_KB_PO_PRILOGU / 1024 }} MB po fajlu i
                        {{ \App\Http\Requests\PodrskaRequest::MAKS_KB_UKUPNO / 1024 }} MB ukupno.
                    </div>
                    <ul class="small text-body-secondary mb-0 mt-1" data-lista-fajlova="podrskaPrilozi"></ul>
                </div>

                <p class="small text-body-secondary mb-0">
                    Ne šaljite JMBG, broj računa ni lična dokumenta ako nisu neophodni. Poruka i prilozi se čuvaju uz vaš nalog
                    i brišu se zajedno sa njim (<a href="{{ route('pravno.politika') }}">Politika privatnosti</a>).
                </p>

                <div class="alert mt-3 mb-0 d-none" role="status" aria-live="polite" data-podrska-status></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Otkaži</button>
                <button type="submit" class="btn btn-primary">Pošalji</button>
            </div>
        </form>
    </div>
</div>
