@extends('layouts.knjiga')

@use('App\Support\Decimal')

@section('naslov', 'Pregled')

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

        {{-- Period je ovde, a ne u zaglavlju stranice: odnosi se samo na porez, ne na otvorene pozicije. --}}
        <section class="mb-4" aria-labelledby="porez-naslov">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0" id="porez-naslov">Porez za period</h2>
                @include('porezi._period')
            </div>

            <div class="row g-4">
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
                        <div class="col-6 stavka d-flex flex-column">
                            <dt>Neto kapitalna dobit</dt>
                            <dd @class(['mt-auto', 'dobit' => $kapitalnaDobit['neto_dobit'] > Decimal::nula(), 'gubitak' => $kapitalnaDobit['neto_dobit'] < Decimal::nula()])>
                                {{ Decimal::format($kapitalnaDobit['neto_dobit']) }}
                            </dd>
                        </div>
                        <div class="col-6 stavka d-flex flex-column">
                            <dt>Gubitak za prebijanje</dt>
                            <dd class="mt-auto">{{ Decimal::format($kapitalnaDobit['preostalo_za_prebijanje']) }}</dd>
                        </div>
                        <div class="col-6 stavka d-flex flex-column">
                            <dt>Dividende, bruto</dt>
                            <dd class="mt-auto">{{ Decimal::format($dividende['bruto_rsd']) }}</dd>
                        </div>
                        <div class="col-6 stavka d-flex flex-column">
                            <dt>Porez na dividende za uplatu</dt>
                            <dd class="mt-auto">{{ Decimal::format($dividende['za_uplatu']) }}</dd>
                        </div>
                    </dl>
                    <p class="text-body-secondary small mb-0">Svi iznosi su u dinarima, po srednjem kursu NBS na dan transakcije.</p>
                </div>
            </div>
        </section>

        <section aria-labelledby="portfolio-naslov">
            <h2 class="h5 mb-1" id="portfolio-naslov">Otvorene pozicije</h2>
            <p class="text-body-secondary small">Preostale akcije po FIFO redosledu i njihova nabavna vrednost. Ne zavisi od izabranog perioda.</p>

            @if ($brojPozicija === 0)
                <p class="text-body-secondary">Nema otvorenih pozicija.</p>
            @else
                <x-tabela id="portfolio" :tabela="$tabela" :redovi="$portfolio" :ukupno="$brojPozicija" placeholder="Simbol, naziv, ISIN ili iznos…" />
            @endif
        </section>
    @endif
@endsection
