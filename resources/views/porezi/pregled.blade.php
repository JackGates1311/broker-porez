@extends('layouts.knjiga')

@use('App\Support\Decimal')

@section('naslov', 'Pregled')

@section('alati')
    @include('porezi._period')
@endsection

@section('knjiga')
    @if (! $imaTransakcija)
        <div class="prazno">
            <h2 class="h5">Još nema transakcija</h2>
            <p class="text-body-secondary mb-3">
                Uvezite Trading 212 izvod (History → Export CSV). Kursevi NBS se preuzimaju automatski.
            </p>
            <a href="{{ route('uvoz') }}" class="btn btn-primary">Uvezi CSV</a>
        </div>
    @else
        @if ($brojBezKursa > 0 || $prodajeBezIstorije > 0)
            <div class="alert alert-warning d-flex flex-column gap-1" role="alert">
                @if ($brojBezKursa > 0)
                    <span>Transakcija bez NBS kursa: <strong>{{ $brojBezKursa }}</strong>. Porez za njih nije izračunat.
                        <a href="{{ route('uvoz') }}" class="alert-link">Preuzmi kurseve</a></span>
                @endif
                @if ($prodajeBezIstorije > 0)
                    <span>Prodaja bez potpune istorije kupovina: <strong>{{ $prodajeBezIstorije }}</strong>. Nabavna vrednost je umanjena, a porez preveliki.
                        Uvezite starije Trading 212 izvode.</span>
                @endif
            </div>
        @endif

        <div class="row g-4 mb-5">
            <div class="col-lg-7">
                <section class="polje-obrasca h-100" aria-labelledby="dug-naslov">
                    <p class="polje-obrasca-oznaka" id="dug-naslov">
                        Porez na kapitalnu dobit za uplatu ({{ mb_strtolower($period->naziv()) }})
                    </p>
                    <x-kucice :iznos="$kapitalnaDobit['trenutni_dug']" />
                    <p class="text-body-secondary small mt-3 mb-0">
                        Po {{ $kapitalnaDobit['broj_prodaja'] }} {{ $kapitalnaDobit['broj_prodaja'] === 1 ? 'prodaji' : 'prodaja' }}, sa gubicima prebijenim protiv dobitaka iz istog perioda.
                        <a href="{{ route('kapitalna-dobit', ['period' => $period->kod]) }}">Pogledaj obračun</a>
                    </p>
                </section>
            </div>
            <div class="col-lg-5">
                <dl class="row g-0 mb-0">
                    <div class="col-6 stavka">
                        <dt>Neto kapitalna dobit</dt>
                        <dd @class(['dobit' => $kapitalnaDobit['neto_dobit'] > Decimal::nula(), 'gubitak' => $kapitalnaDobit['neto_dobit'] < Decimal::nula()])>
                            {{ Decimal::format($kapitalnaDobit['neto_dobit']) }}
                        </dd>
                    </div>
                    <div class="col-6 stavka">
                        <dt>Gubitak za prebijanje</dt>
                        <dd>{{ Decimal::format($kapitalnaDobit['preostalo_za_prebijanje']) }}</dd>
                    </div>
                    <div class="col-6 stavka">
                        <dt>Dividende, bruto</dt>
                        <dd>{{ Decimal::format($dividende['bruto_rsd']) }}</dd>
                    </div>
                    <div class="col-6 stavka">
                        <dt>Porez na dividende za uplatu</dt>
                        <dd>{{ Decimal::format($dividende['za_uplatu']) }}</dd>
                    </div>
                </dl>
                <p class="text-body-secondary small mb-0">Svi iznosi su u dinarima, po srednjem kursu NBS na dan transakcije.</p>
            </div>
        </div>

        <section aria-labelledby="portfolio-naslov">
            <h2 class="h5 mb-1" id="portfolio-naslov">Otvorene pozicije</h2>
            <p class="text-body-secondary small">Preostale akcije po FIFO redosledu i njihova nabavna vrednost. Ne zavisi od izabranog perioda.</p>

            @if ($portfolio === [])
                <p class="text-body-secondary">Nema otvorenih pozicija.</p>
            @else
                <div class="tabela-omot">
                    <table class="table table-hover tabela-knjiga">
                        <thead>
                            <tr>
                                <th scope="col">Simbol</th>
                                <th scope="col">Naziv</th>
                                <th scope="col">ISIN</th>
                                <th scope="col" class="broj">Količina</th>
                                <th scope="col" class="broj">Nabavna vrednost (RSD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($portfolio as $pozicija)
                                <tr>
                                    <td class="fw-semibold">{{ $pozicija->simbol }}</td>
                                    <td>{{ $pozicija->naziv }}</td>
                                    <td class="text-body-secondary">{{ $pozicija->isin }}</td>
                                    <td class="broj">{{ Decimal::formatKolicina($pozicija->kolicina) }}</td>
                                    <td class="broj">
                                        {{ Decimal::format($pozicija->nabavna_rsd) }}
                                        @if ($pozicija->bez_kursa)
                                            <span class="text-warning" title="Deo kupovina nema NBS kurs">*</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
