@extends('layouts.knjiga')

@use('App\Support\Decimal')

@section('naslov', 'Dividende')

@section('knjiga')
    <div class="row g-4">
        <div class="col-12 order-2">
            @if ($ukupno === 0)
                <div class="prazno">
                    <p class="mb-2">Nema isplaćenih dividendi u periodu „{{ $period->naziv() }}”.</p>
                    <a href="{{ route('uvoz') }}">Uvezi Trading 212 izvod</a>
                </div>
            @else
                {{-- Šifre za PP OPO; dugmad u tabeli šalju ovu formu na adresu konkretne dividende. --}}
                <form id="ppopo-forma" method="GET" action="#"></form>

                <x-tabela id="dividende" :tabela="$tabela" :redovi="$redovi" :ukupno="$ukupno" klasa="table-sm align-middle tabela-gusta">
                    <x-slot:podnozje>
                        <tr>
                            <td colspan="10">Ukupno za period ({{ $zbir['broj'] }}), bez obzira na pretragu</td>
                            <td class="broj">{{ Decimal::format($zbir['bruto_rsd']) }}</td>
                            <td class="broj">{{ Decimal::format($zbir['porez']) }}</td>
                            <td class="broj">{{ Decimal::format($zbir['za_uplatu']) }}</td>
                            <td></td>
                        </tr>
                    </x-slot:podnozje>
                </x-tabela>
                <p class="text-body-secondary small mt-2">
                    Bruto = količina × neto dividenda po akciji + porez plaćen u inostranstvu. Za uplatu je 15% bruto iznosa umanjeno za porez plaćen u inostranstvu.
                </p>
            @endif
        </div>

        <aside class="col-12 order-1" aria-labelledby="porez-naslov">
            {{-- Period je ovde, kao na Pregledu, a ne u zaglavlju stranice. --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0" id="porez-naslov">Porez za period</h2>
                @include('porezi._period', ['samoGodine' => true])
            </div>

            <div class="row g-4">
            <div class="col-lg-4"><section class="polje-obrasca h-100" aria-labelledby="div-naslov">
                <p class="polje-obrasca-oznaka" id="div-naslov">Porez na dividende za uplatu ({{ mb_strtolower($period->naziv()) }})</p>
                <x-kucice :iznos="$zbir['za_uplatu']" />
            </section></div>

            <div class="col-lg-4"><dl class="mb-0">
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Bruto dividende</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['bruto_rsd']) }}</dd>
                </div>
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Obračunat porez 15%</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['porez']) }}</dd>
                </div>
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Plaćeno u inostranstvu</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['placen_porez_rsd']) }}</dd>
                </div>
            </dl></div>

            <div class="col-lg-4"><section class="border bg-white p-3 h-100" aria-labelledby="ppopo-naslov">
                <h2 class="h6 mb-1" id="ppopo-naslov">Obrazac PP OPO</h2>
                <p class="small text-body-secondary">Podnosi se za svaku isplatu, u roku od 30 dana. Preuzmite ga ikonicom u koloni „PP OPO” u redu dividende.</p>

                @unless ($obaveznikPopunjen)
                    <p class="small text-warning-emphasis">Deo 2 će ostati prazan dok ne popunite <a href="{{ route('poreski-obaveznik') }}">podatke o poreskom obavezniku</a>.</p>
                @endunless

                <fieldset class="small">
                    <legend class="fs-6 small fw-semibold mb-2">Šifre na obrascu</legend>
                    <div class="mb-2">
                        <label for="sifra_vrste_prihoda" class="form-label mb-1">4.2 Šifra vrste prihoda</label>
                        <input id="sifra_vrste_prihoda" form="ppopo-forma" name="sifra_vrste_prihoda" list="sifre-vrste-prihoda" class="form-control form-control-sm" inputmode="numeric" maxlength="9" aria-describedby="sifra-vrste-prihoda-napomena" value="{{ config('porezi.ppopo.sifra_vrste_prihoda') }}">
                        <datalist id="sifre-vrste-prihoda">
                            @foreach (config('porezi.ppopo.sifre_vrste_prihoda') as $sifra => $naziv)
                                <option value="{{ $sifra }}">{{ $naziv }}</option>
                            @endforeach
                        </datalist>
                        <div id="sifra-vrste-prihoda-napomena" class="form-text">Nije potvrđena u šifarniku Poreske uprave. Proverite je pre podnošenja.</div>
                    </div>
                    <div class="mb-2">
                        <label for="vrsta_prijave" class="form-label mb-1">1.1 Vrsta prijave</label>
                        <select id="vrsta_prijave" form="ppopo-forma" name="vrsta_prijave" class="form-select form-select-sm">
                            @foreach (config('porezi.ppopo.vrste_prijave') as $oznaka => $naziv)
                                <option value="{{ $oznaka }}" @selected((string) $oznaka === config('porezi.ppopo.vrsta_prijave'))>{{ $oznaka }} – {{ $naziv }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="nacin_ostvarivanja" class="form-label mb-1">3.1 Način ostvarivanja prihoda</label>
                        <select id="nacin_ostvarivanja" form="ppopo-forma" name="nacin_ostvarivanja" class="form-select form-select-sm">
                            @foreach (config('porezi.ppopo.nacini_ostvarivanja') as $oznaka => $naziv)
                                <option value="{{ $oznaka }}" @selected((string) $oznaka === config('porezi.ppopo.nacin_ostvarivanja'))>{{ $oznaka }} – {{ $naziv }}</option>
                            @endforeach
                        </select>
                    </div>
                </fieldset>
            </section></div>
            </div>
        </aside>
    </div>
@endsection
