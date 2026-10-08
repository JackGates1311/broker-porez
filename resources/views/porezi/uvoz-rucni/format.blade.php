@extends('layouts.knjiga')

@section('naslov', 'Uvoz i transakcije')

@section('knjiga')
    @include('porezi.uvoz-rucni._koraci')

    <section class="border bg-white p-4 mb-4" aria-labelledby="format-naslov">
        <h2 class="h5" id="format-naslov">Format fajla</h2>
        <p class="text-body-secondary small">
            Podesite kako je fajl zapisan dok pregled ispod ne prikaže kolone ispravno razdvojene, sa zaglavljem u označenom redu.
        </p>

        {{-- Promena polja čuva izbor i osvežava pregled (data-auto-submit); "Dalje" ide na mapiranje kolona. --}}
        <form method="POST" action="{{ route('uvoz.rucni.format.sacuvaj') }}" data-auto-submit>
            @csrf
            <div class="row g-3">
                <div class="col-sm-6 col-xl">
                    <label for="separator" class="form-label">Separator kolona</label>
                    <select id="separator" name="separator" class="form-select @error('separator') is-invalid @enderror">
                        @foreach (\App\Services\Uvoz\Rucni\PodesavanjaUvoza::SEPARATORI as $vrednost => $naziv)
                            <option value="{{ \App\Services\Uvoz\Rucni\PodesavanjaUvoza::kodZnaka($vrednost) }}" @selected($p->separator === $vrednost)>{{ $naziv }}</option>
                        @endforeach
                    </select>
                    @error('separator')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6 col-xl">
                    <label for="kodna_strana" class="form-label">Kodna strana</label>
                    <select id="kodna_strana" name="kodna_strana" class="form-select @error('kodna_strana') is-invalid @enderror">
                        @foreach (\App\Services\Uvoz\Rucni\PodesavanjaUvoza::KODNE_STRANE as $vrednost => $naziv)
                            <option value="{{ $vrednost }}" @selected($p->kodnaStrana === $vrednost)>{{ $naziv }}</option>
                        @endforeach
                    </select>
                    @error('kodna_strana')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6 col-xl">
                    <label for="red_zaglavlja" class="form-label">Red zaglavlja</label>
                    <input type="number" id="red_zaglavlja" name="red_zaglavlja" min="1" max="100" required
                           value="{{ old('red_zaglavlja', $p->redZaglavlja) }}"
                           class="form-control @error('red_zaglavlja') is-invalid @enderror">
                    @error('red_zaglavlja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6 col-xl">
                    <label for="decimalni_separator" class="form-label">Decimalni separator</label>
                    <select id="decimalni_separator" name="decimalni_separator" class="form-select @error('decimalni_separator') is-invalid @enderror">
                        @foreach (\App\Services\Uvoz\Rucni\PodesavanjaUvoza::DECIMALNI_SEPARATORI as $vrednost => $naziv)
                            <option value="{{ $vrednost }}" @selected($p->decimalniSeparator === $vrednost)>{{ $naziv }}</option>
                        @endforeach
                    </select>
                    @error('decimalni_separator')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6 col-xl">
                    <label for="separator_hiljada" class="form-label">Separator hiljada</label>
                    <select id="separator_hiljada" name="separator_hiljada" class="form-select @error('separator_hiljada') is-invalid @enderror">
                        @foreach (\App\Services\Uvoz\Rucni\PodesavanjaUvoza::SEPARATORI_HILJADA as $vrednost => $naziv)
                            <option value="{{ \App\Services\Uvoz\Rucni\PodesavanjaUvoza::kodZnaka((string) $vrednost) }}" @selected($p->separatorHiljada === (string) $vrednost)>{{ $naziv }}</option>
                        @endforeach
                    </select>
                    @error('separator_hiljada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <noscript><button type="submit" class="btn btn-outline-primary">Osveži pregled</button></noscript>
                <button type="submit" name="dalje" value="1" class="btn btn-primary">Dalje: kolone</button>
            </div>
        </form>
    </section>

    <section aria-labelledby="pregled-naslov">
        <h2 class="h5 mb-1" id="pregled-naslov">Pregled početka fajla</h2>
        <p class="text-body-secondary small">Označen je red zaglavlja; redovi iznad njega se preskaču.</p>

        @if ($pocetak === [])
            <div class="prazno">Fajl je prazan ili nije čitljiv sa izabranim podešavanjima.</div>
        @else
            <div class="table-responsive border bg-white">
                <table class="table tabela-knjiga tabela-gusta mb-0">
                    <tbody>
                        @foreach ($pocetak as $i => $celije)
                            <tr @class(['red-zaglavlja' => $i + 1 === $p->redZaglavlja, 'text-body-secondary' => $i + 1 < $p->redZaglavlja])>
                                <th scope="row" class="text-body-secondary small">{{ $i + 1 }}</th>
                                @foreach ($celije as $celija)
                                    <td class="text-nowrap" title="{{ $celija }}">{{ \Illuminate\Support\Str::limit($celija, 40) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
