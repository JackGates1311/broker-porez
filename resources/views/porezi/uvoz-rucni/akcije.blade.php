@extends('layouts.knjiga')

@section('naslov', 'Uvoz i transakcije')

@section('knjiga')
    @include('porezi.uvoz-rucni._koraci')

    <section class="border bg-white p-4" aria-labelledby="akcije-naslov">
        <h2 class="h5" id="akcije-naslov">Mapiranje akcija</h2>
        <p class="text-body-secondary small">
            Ovo su sve različite vrednosti kolone akcije u fajlu. Odredite šta svaka znači. Redovi tipa „Ostalo”
            (kamata, isplata, konverzija valute…) se čuvaju, ali ne ulaze u obračun poreza.
        </p>

        @if ($vrednosti === [])
            <div class="prazno">Kolona akcije je prazna u svim redovima. Vratite se i izaberite drugu kolonu.</div>
            <div class="mt-4">
                <a href="{{ route('uvoz.rucni.kolone') }}" class="btn btn-outline-secondary">Nazad</a>
            </div>
        @else
            <form method="POST" action="{{ route('uvoz.rucni.akcije.sacuvaj') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table tabela-knjiga mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Vrednost u fajlu</th>
                                <th scope="col" class="broj">Redova</th>
                                <th scope="col">Tip transakcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vrednosti as $vrednost => $broj)
                                <tr>
                                    <th scope="row" class="fw-semibold">
                                        <label for="akcija-{{ $loop->index }}">{{ $vrednost }}</label>
                                        <input type="hidden" name="akcije[{{ $loop->index }}][vrednost]" value="{{ $vrednost }}">
                                    </th>
                                    <td class="broj">{{ $broj }}</td>
                                    <td style="min-width: 12rem">
                                        <select id="akcija-{{ $loop->index }}" name="akcije[{{ $loop->index }}][tip]" class="form-select form-select-sm">
                                            @foreach (\App\Enums\TipTransakcije::cases() as $tip)
                                                <option value="{{ $tip->value }}" @selected($tipovi[(string) $vrednost] === $tip)>{{ $tip->naziv() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between gap-2 mt-4">
                    <a href="{{ route('uvoz.rucni.kolone') }}" class="btn btn-outline-secondary">Nazad</a>
                    <button type="submit" class="btn btn-primary">Dalje: pregled</button>
                </div>
            </form>
        @endif
    </section>
@endsection
