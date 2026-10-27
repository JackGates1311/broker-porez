{{-- Pitanje o zadovoljstvu (samo insajderi, Korisnik::trebaPitatiZadovoljstvo()). Skriveno dok se ne klikne dugme sa
     data-izvoz-obrasca; prikazuje ga i šalje resources/js/zadovoljstvo.ts. --}}
<aside class="card zadovoljstvo-kartica d-none" aria-labelledby="zadovoljstvoNaslov" aria-live="polite" data-zadovoljstvo>
    <form method="POST" action="{{ route('zadovoljstvo.sacuvaj') }}" class="card-body">
        @csrf
        <input type="hidden" name="povod" value="">

        <div class="d-flex align-items-start justify-content-between gap-2">
            <h2 id="zadovoljstvoNaslov" class="h6 mb-1">Koliko ste zadovoljni BrokerPorezom?</h2>
            <button type="submit" name="akcija" value="kasnije" class="btn-close" aria-label="Ne sada"></button>
        </div>
        <p class="small text-body-secondary mb-2">Upravo ste preuzeli obrazac. Ocena nam pomaže da ga poboljšamo.</p>

        <div class="btn-group w-100" role="group" aria-label="Ocena od 1 (nimalo) do 5 (potpuno)">
            @foreach (range(1, 5) as $ocena)
                <input type="radio" class="btn-check" name="ocena" id="zadovoljstvoOcena{{ $ocena }}" value="{{ $ocena }}" autocomplete="off">
                <label class="btn btn-outline-primary" for="zadovoljstvoOcena{{ $ocena }}">{{ $ocena }}</label>
            @endforeach
        </div>
        <div class="d-flex justify-content-between small text-body-secondary mt-1 mb-2" aria-hidden="true">
            <span>nimalo</span><span>potpuno</span>
        </div>

        <label for="zadovoljstvoKomentar" class="visually-hidden">Komentar</label>
        <textarea id="zadovoljstvoKomentar" name="komentar" rows="2" maxlength="2000" class="form-control form-control-sm mb-2"
                  placeholder="Šta bi moglo bolje? (nije obavezno)"></textarea>

        <p class="small text-danger mb-2 d-none" role="alert" data-zadovoljstvo-greska></p>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="submit" name="akcija" value="posalji" class="btn btn-primary btn-sm">Pošalji</button>
            <button type="submit" name="akcija" value="kasnije" class="btn btn-outline-secondary btn-sm">Ne sada</button>
            <button type="submit" name="akcija" value="nikad" class="btn btn-link btn-sm ms-auto px-0">Ne pitaj više</button>
        </div>
    </form>
</aside>
