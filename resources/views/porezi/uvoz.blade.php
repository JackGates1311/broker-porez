@extends('layouts.knjiga')

@section('naslov', 'Uvoz i transakcije')

@section('knjiga')
    <div class="row g-4 mb-5">
        <section class="col-lg-6" aria-labelledby="uvoz-naslov">
            <div class="border bg-white p-4 h-100">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h2 class="h5 mb-0" id="uvoz-naslov">Uvoz izvoda</h2>
                    {{-- Izbor brokera; menja se odmah (data-auto-submit), bez JS-a preko dugmeta. --}}
                    <form method="GET" class="d-flex align-items-center gap-2" data-auto-submit>
                        <label for="izvor" class="text-body-secondary small text-nowrap">Broker:</label>
                        <select id="izvor" name="izvor" class="form-select form-select-sm" style="min-width: 14rem">
                            @foreach (\App\Services\Uvoz\IzvorUvoza::cases() as $opcija)
                                <option value="{{ $opcija->kod() }}" @selected($opcija === $izvor) @disabled(! $opcija->dostupan())>
                                    {{ $opcija->naziv() }}@unless ($opcija->dostupan()) (uskoro)@endunless
                                </option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="btn btn-sm btn-outline-primary">Prikaži</button></noscript>
                    </form>
                </div>

                @if ($izvor === \App\Services\Uvoz\IzvorUvoza::Trading212)
                    <p class="text-body-secondary small">
                        U aplikaciji Trading 212: History → Export, izaberite period i preuzmite CSV. Jedan izvod pokriva najviše 12 meseci,
                        pa za celu istoriju uvezite sve fajlove odjednom. Transakcije koje već postoje se preskaču.
                    </p>
                    <form method="POST" action="{{ route('uvoz.trading212') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="fajlovi" class="form-label">CSV fajlovi</label>
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <div class="unos-fajla">
                                <input type="file" id="fajlovi" name="fajlovi[]" accept=".csv,text/csv" multiple required
                                       class="form-control @error('fajlovi') is-invalid @enderror @error('fajlovi.*') is-invalid @enderror">
                                @error('fajlovi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @error('fajlovi.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Uvezi transakcije</button>
                        </div>
                        <ul class="small text-body-secondary mt-2 mb-0 ps-3" data-lista-fajlova="fajlovi"></ul>
                    </form>
                @else
                    <p class="text-body-secondary small">
                        CSV izvod bilo kog brokera. U nekoliko koraka birate format fajla, povezujete njegove kolone sa poljima transakcije
                        i određujete šta znači svaka vrednost akcije. Mapiranje možete sačuvati kao šablon za sledeći izvod istog brokera.
                    </p>
                    <form method="POST" action="{{ route('uvoz.rucni') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="sablon_id" class="form-label">Šablon</label>
                        <select id="sablon_id" name="sablon_id" class="form-select mb-3">
                            <option value="">Novi šablon (mapiranje od početka)</option>
                            @foreach ($sabloni as $sablon)
                                <option value="{{ $sablon->id }}" @selected((string) old('sablon_id') === (string) $sablon->id)>{{ $sablon->naziv }}</option>
                            @endforeach
                        </select>

                        <label for="fajl_rucni" class="form-label">CSV fajl</label>
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <div class="unos-fajla">
                                <input type="file" id="fajl_rucni" name="fajl_rucni" accept=".csv,.txt,text/csv,text/plain" required
                                       class="form-control @error('fajl_rucni') is-invalid @enderror">
                                @error('fajl_rucni')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Nastavi</button>
                        </div>
                    </form>

                    @if ($sabloni->isNotEmpty())
                        <h3 class="h6 mt-4 mb-2">Sačuvani šabloni</h3>
                        <ul class="list-unstyled small mb-0">
                            @foreach ($sabloni as $sablon)
                                <li class="d-flex align-items-center justify-content-between gap-2 border-top py-1">
                                    <span>{{ $sablon->naziv }}</span>
                                    <form method="POST" action="{{ route('uvoz.sabloni.obrisi', $sablon) }}"
                                          data-potvrda="Obrisati šablon „{{ $sablon->naziv }}”?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Obriši</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
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
                    <input type="hidden" name="izvor" value="{{ $izvor->kod() }}">
                    <button type="submit" class="btn btn-primary btn-sm" @disabled($bezKursa->isEmpty())>Preuzmi kurseve koji nedostaju</button>
                </form>

                <form method="POST" action="{{ route('uvoz.kursevi') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="izvor" value="{{ $izvor->kod() }}">
                    <label for="fajl" class="form-label">NBS kursna lista (CSV)</label>
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <div class="unos-fajla">
                            <input type="file" id="fajl" name="fajl" accept=".csv,text/csv" required class="form-control @error('fajl') is-invalid @enderror">
                            @error('fajl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Uvezi kurseve</button>
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
