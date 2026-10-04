@extends('layouts.knjiga')

@section('naslov', 'Uvoz i transakcije')

@section('knjiga')
    <div class="row g-4 mb-5">
        <section class="col-lg-6" aria-labelledby="t212-naslov">
            <div class="border bg-white p-4 h-100">
                <h2 class="h5" id="t212-naslov">Trading 212 izvod</h2>
                <p class="text-body-secondary small">
                    U aplikaciji Trading 212: History → Export, izaberite period i preuzmite CSV. Jedan izvod pokriva najviše 12 meseci,
                    pa za celu istoriju uvezite sve fajlove odjednom. Transakcije koje već postoje se preskaču.
                </p>
                <form method="POST" action="{{ route('uvoz.trading212') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="fajlovi" class="form-label">CSV fajlovi</label>
                    <input type="file" id="fajlovi" name="fajlovi[]" accept=".csv,text/csv" multiple required
                           class="form-control @error('fajlovi') is-invalid @enderror @error('fajlovi.*') is-invalid @enderror">
                    @error('fajlovi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('fajlovi.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <ul class="small text-body-secondary mt-2 mb-0 ps-3" data-lista-fajlova="fajlovi"></ul>
                    <button type="submit" class="btn btn-primary mt-3">Uvezi transakcije</button>
                </form>
            </div>
        </section>

        <section class="col-lg-6" aria-labelledby="kursevi-naslov">
            <div class="border bg-white p-4 h-100">
                <h2 class="h5" id="kursevi-naslov">Srednji kurs NBS</h2>
                <p class="text-body-secondary small">
                    Kursevi se preuzimaju automatski pri uvozu. Ako preuzimanje ne uspe, pokušajte ponovo ili uvezite kursnu listu
                    sa sajta NBS (CSV sa kolonom „Средњи курс”).
                </p>

                @if ($bezKursa->isNotEmpty())
                    <p class="small mb-2"><strong>{{ $bezKursa->count() }}</strong> transakcija čeka kurs:</p>
                    <ul class="small text-body-secondary mb-3">
                        @foreach ($bezKursa->take(8) as $t)
                            <li>{{ $t->vremeSrb()->format('d.m.Y') }}, {{ $t->imovina?->simbol ?? $t->tip_akcije }} ({{ $t->valuta_cene ?? $t->valuta_poreza }})</li>
                        @endforeach
                        @if ($bezKursa->count() > 8)<li>i još {{ $bezKursa->count() - 8 }}</li>@endif
                    </ul>
                @else
                    <p class="small text-success mb-3">Sve transakcije imaju kurs.</p>
                @endif

                <form method="POST" action="{{ route('kursevi.osvezi') }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm" @disabled($bezKursa->isEmpty())>Preuzmi kurseve koji nedostaju</button>
                </form>

                <form method="POST" action="{{ route('uvoz.kursevi') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="fajl" class="form-label small">NBS kursna lista (CSV)</label>
                    <div class="input-group input-group-sm has-validation">
                        <input type="file" id="fajl" name="fajl" accept=".csv,text/csv" required class="form-control @error('fajl') is-invalid @enderror">
                        <button type="submit" class="btn btn-outline-primary">Uvezi kurseve</button>
                        @error('fajl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </form>
            </div>
        </section>
    </div>

    <section aria-labelledby="transakcije-naslov">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <div>
                <h2 class="h5 mb-1" id="transakcije-naslov">Sve transakcije</h2>
                <p class="text-body-secondary small mb-0">Sirovi redovi iz izvoda, podrazumevano najnoviji prvi. Ukupno: {{ $ukupno }}.</p>
            </div>
            @if ($ukupno > 0)
                <form method="POST" action="{{ route('transakcije.obrisi') }}"
                      data-potvrda="Obrisati svih {{ $ukupno }} transakcija i obračun? Ovo ne može da se poništi.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Obriši sve transakcije</button>
                </form>
            @endif
        </div>

        @if ($ukupno === 0)
            <div class="prazno">Još nema uvezenih transakcija.</div>
        @else
            <x-tabela id="transakcije" :tabela="$tabela" :redovi="$transakcije" :ukupno="$ukupno" />
        @endif
    </section>
@endsection
