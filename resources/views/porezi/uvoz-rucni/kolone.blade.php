@extends('layouts.knjiga')

@section('naslov', 'Uvoz i transakcije')

@section('knjiga')
    @include('porezi.uvoz-rucni._koraci')

    <section class="border bg-white p-4" aria-labelledby="kolone-naslov">
        <h2 class="h5" id="kolone-naslov">Mapiranje kolona</h2>
        <p class="text-body-secondary small">
            Za svako polje transakcije izaberite kolonu iz fajla. Pored kolone je vrednost iz prvog reda. Polja koja fajl nema ostavite prazna;
            za valutu možete upisati jednu vrednost za ceo fajl.
        </p>

        @if ($nedostaju !== [])
            <div class="alert alert-warning small" role="alert">
                Fajl nema kolone iz šablona: <strong>{{ implode(', ', $nedostaju) }}</strong>. Izaberite odgovarajuće kolone ponovo.
            </div>
        @endif

        @error('kolone')<div class="alert alert-danger small" role="alert">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('uvoz.rucni.kolone.sacuvaj') }}">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="format_datuma" class="form-label">Format datuma i vremena</label>
                    <select id="format_datuma" name="format_datuma" class="form-select @error('format_datuma') is-invalid @enderror">
                        @foreach (\App\Services\Uvoz\Rucni\PodesavanjaUvoza::FORMATI_DATUMA as $format => $primerFormata)
                            <option value="{{ $format }}" @selected(old('format_datuma', $p->formatDatuma) === $format)>{{ $primerFormata }}</option>
                        @endforeach
                    </select>
                    @error('format_datuma')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="vremenska_zona" class="form-label">Vremenska zona izvoda</label>
                    <select id="vremenska_zona" name="vremenska_zona" aria-describedby="zonaPomoc"
                            class="form-select @error('vremenska_zona') is-invalid @enderror">
                        @foreach (\App\Services\Uvoz\Rucni\PodesavanjaUvoza::VREMENSKE_ZONE as $zona => $naziv)
                            <option value="{{ $zona }}" @selected(old('vremenska_zona', $p->vremenskaZona) === $zona)>{{ $naziv }}</option>
                        @endforeach
                    </select>
                    <div id="zonaPomoc" class="form-text">Kurs NBS se uzima za datum po beogradskom vremenu.</div>
                    @error('vremenska_zona')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="table-responsive">
                <table class="table tabela-knjiga mb-0 align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Polje transakcije</th>
                            <th scope="col">Kolona u fajlu</th>
                            <th scope="col">Ista vrednost za ceo fajl</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Services\Uvoz\Rucni\PoljeUvoza::cases() as $polje)
                            @php
                                $ime = "kolone.{$polje->value}";
                                $izabrana = old("{$ime}.kolona", $p->kolonaZa($polje));
                            @endphp
                            <tr>
                                <th scope="row" class="fw-normal">
                                    <label for="kolona-{{ $polje->value }}" class="fw-semibold">{{ $polje->naziv() }}</label>
                                    @if ($polje->obavezno())<span class="text-danger" title="obavezno">*</span>@endif
                                    @if ($polje->pomoc())<div class="small text-body-secondary">{{ $polje->pomoc() }}</div>@endif
                                </th>
                                <td style="min-width: 16rem">
                                    <select id="kolona-{{ $polje->value }}" name="kolone[{{ $polje->value }}][kolona]"
                                            class="form-select form-select-sm @error("{$ime}.kolona") is-invalid @enderror">
                                        <option value="">— nije u fajlu —</option>
                                        @foreach ($zaglavlje as $kolona)
                                            <option value="{{ $kolona }}" @selected($izabrana === $kolona)>
                                                {{ $kolona }}@if (filled($primer[$kolona] ?? null)) — {{ \Illuminate\Support\Str::limit($primer[$kolona], 30) }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("{$ime}.kolona")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </td>
                                <td>
                                    @if ($polje->dozvoljavaKonstantu())
                                        <input type="text" name="kolone[{{ $polje->value }}][konstanta]" maxlength="3" placeholder="npr. USD"
                                               aria-label="{{ $polje->naziv() }} za ceo fajl"
                                               value="{{ old("{$ime}.konstanta", $p->konstantaZa($polje)) }}"
                                               class="form-control form-control-sm text-uppercase @error("{$ime}.konstanta") is-invalid @enderror"
                                               style="max-width: 7rem">
                                        @error("{$ime}.konstanta")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between gap-2 mt-4">
                <a href="{{ route('uvoz.rucni.format') }}" class="btn btn-outline-secondary">Nazad</a>
                <button type="submit" class="btn btn-primary">Dalje: akcije</button>
            </div>
        </form>
    </section>
@endsection
