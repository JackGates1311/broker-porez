@extends('layouts.knjiga')

@use('App\Support\Decimal')

@section('naslov', 'Dividende')

@section('alati')
    @include('porezi._period', ['samoGodine' => true])
@endsection

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

                <x-tabela id="dividende" :tabela="$tabela" :redovi="$redovi" :ukupno="$ukupno" klasa="table-sm align-middle">
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

        <aside class="col-12 order-1">
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
                <p class="small text-body-secondary">Podnosi se za svaku isplatu, u roku od 30 dana. Preuzmite ga dugmetom „PP OPO” u redu dividende.</p>

                @unless ($profilPopunjen)
                    <p class="small text-warning-emphasis">Deo 2 će ostati prazan dok ne popunite <a href="{{ route('profil') }}">poreski profil</a>.</p>
                @endunless

                <details class="small">
                    <summary class="text-body-secondary">Šifre na obrascu</summary>
                    <p class="text-body-secondary mt-2 mb-2">Podrazumevane vrednosti nisu proverene u šifarniku Poreske uprave. Proverite ih pre podnošenja.</p>
                    <div class="mb-2">
                        <label for="sifra_vrste_prihoda" class="form-label mb-1">4.2 Šifra vrste prihoda</label>
                        <input id="sifra_vrste_prihoda" form="ppopo-forma" name="sifra_vrste_prihoda" class="form-control form-control-sm" inputmode="numeric" maxlength="9" value="{{ config('porezi.ppopo.sifra_vrste_prihoda') }}">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="vrsta_prijave" class="form-label mb-1">1.1 Vrsta prijave</label>
                            <input id="vrsta_prijave" form="ppopo-forma" name="vrsta_prijave" class="form-control form-control-sm" inputmode="numeric" value="{{ config('porezi.ppopo.vrsta_prijave') }}">
                        </div>
                        <div class="col-6">
                            <label for="nacin_ostvarivanja" class="form-label mb-1">3.1 Način ostvarivanja</label>
                            <input id="nacin_ostvarivanja" form="ppopo-forma" name="nacin_ostvarivanja" class="form-control form-control-sm" inputmode="numeric" value="{{ config('porezi.ppopo.nacin_ostvarivanja') }}">
                        </div>
                    </div>
                </details>
            </section></div>
            </div>
        </aside>
    </div>
@endsection
