@extends('layouts.knjiga')

@section('naslov', 'Uvoz i transakcije')

@php use App\Enums\TipTransakcije; use App\Support\Decimal; @endphp

@section('knjiga')
    @include('porezi.uvoz-rucni._koraci')

    <div class="row g-4 mb-4">
        <section class="col-lg-7" aria-labelledby="rezime-naslov">
            <div class="border bg-white p-4 h-100">
                <h2 class="h5" id="rezime-naslov">Šta će biti uvezeno</h2>
                <p class="mb-2">
                    Ispravnih redova: <strong>{{ $ukupno }}</strong>
                    @if ($greske !== [])
                        · preskočeno zbog grešaka: <strong class="text-danger">{{ count($greske) }}</strong>
                    @endif
                </p>
                @if ($poTipu !== [])
                    <ul class="list-inline small mb-0">
                        @foreach (TipTransakcije::cases() as $tip)
                            @if (isset($poTipu[$tip->value]))
                                <li class="list-inline-item">{{ $tip->naziv() }}: <strong>{{ $poTipu[$tip->value] }}</strong></li>
                            @endif
                        @endforeach
                    </ul>
                @endif
                <p class="small text-body-secondary mt-3 mb-0">
                    Transakcije koje već postoje se preskaču, pa ponovni uvoz istog fajla ne pravi duplikate.
                    Posle uvoza se preuzimaju kursevi NBS i preračunava obračun.
                </p>
            </div>
        </section>

        <section class="col-lg-5" aria-labelledby="uvoz-potvrda-naslov">
            <form method="POST" action="{{ route('uvoz.rucni.uvezi') }}" class="border bg-white p-4 h-100">
                @csrf
                <h2 class="h5" id="uvoz-potvrda-naslov">Uvoz</h2>
                <div class="form-check mb-2">
                    <input type="checkbox" id="sacuvaj_sablon" name="sacuvaj_sablon" value="1" class="form-check-input"
                           @checked(old('sacuvaj_sablon', $carobnjak->sablonNaziv() !== null))>
                    <label for="sacuvaj_sablon" class="form-check-label">
                        {{ $carobnjak->sablonNaziv() !== null ? 'Sačuvaj izmene u šablonu' : 'Sačuvaj mapiranje kao šablon' }}
                    </label>
                </div>
                <label for="naziv_sablona" class="form-label small">Naziv šablona</label>
                <input type="text" id="naziv_sablona" name="naziv_sablona" maxlength="100" placeholder="npr. Moj broker"
                       value="{{ old('naziv_sablona', $carobnjak->sablonNaziv()) }}" aria-describedby="nazivSablonaPomoc"
                       class="form-control @error('naziv_sablona') is-invalid @enderror">
                <div id="nazivSablonaPomoc" class="form-text">Šablon sa istim nazivom se zamenjuje.</div>
                @error('naziv_sablona')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="d-flex justify-content-between gap-2 mt-4">
                    <a href="{{ route('uvoz.rucni.akcije') }}" class="btn btn-outline-secondary">Nazad</a>
                    <button type="submit" class="btn btn-primary" @disabled($ukupno === 0)>Uvezi transakcije</button>
                </div>
            </form>
        </section>
    </div>

    @if ($greske !== [])
        <section class="alert alert-warning" role="alert" aria-labelledby="greske-naslov">
            <h2 class="h6" id="greske-naslov">Redovi koji neće biti uvezeni</h2>
            <ul class="mb-0 small">
                @foreach (array_slice($greske, 0, 50) as $greska)
                    <li>{{ $greska }}</li>
                @endforeach
                @if (count($greske) > 50)<li>i još {{ count($greske) - 50 }}</li>@endif
            </ul>
        </section>
    @endif

    @if ($redovi !== [])
        <section aria-labelledby="redovi-naslov">
            <h2 class="h5 mb-1" id="redovi-naslov">Prvih {{ count($redovi) }} redova</h2>
            <p class="text-body-secondary small">Vrednosti onako kako će biti upisane; datum je po beogradskom vremenu.</p>
            <div class="table-responsive border bg-white">
                <table class="table tabela-knjiga mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Datum i vreme (SRB)</th>
                            <th scope="col">Tip</th>
                            <th scope="col">Akcija</th>
                            <th scope="col">ISIN</th>
                            <th scope="col">Simbol</th>
                            <th scope="col" class="broj">Količina</th>
                            <th scope="col" class="broj">Cena</th>
                            <th scope="col" class="broj">Ukupno</th>
                            <th scope="col" class="broj">Porez po odbitku</th>
                            <th scope="col" class="broj">Naknade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($redovi as $red)
                            <tr>
                                <td class="text-nowrap">{{ $red->vremeUtc->setTimezone('Europe/Belgrade')->format('d.m.Y H:i:s') }}</td>
                                <td>{{ $red->tip->naziv() }}</td>
                                <td class="text-body-secondary">{{ $red->akcija }}</td>
                                <td>{{ $red->isin }}</td>
                                <td class="fw-semibold">{{ $red->simbol }}</td>
                                <td class="broj">{{ $red->valutaCene ? Decimal::formatKolicina($red->kolicina) : '' }}</td>
                                <td class="broj text-nowrap">{{ $red->valutaCene ? Decimal::format($red->cenaPoAkciji, 4).' '.$red->valutaCene : '' }}</td>
                                <td class="broj text-nowrap">{{ Decimal::format($red->ukupno).' '.$red->valutaUkupno }}</td>
                                <td class="broj text-nowrap">{{ $red->porezPoOdbitku ? Decimal::format($red->porezPoOdbitku).' '.$red->valutaPoreza : '' }}</td>
                                <td class="broj text-nowrap">{{ $red->provizija ? Decimal::format($red->provizija).' '.$red->valutaProvizije : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
