@extends('layouts.knjiga')

@use('App\Support\Decimal')
@use('App\Enums\TipTransakcije')

@section('naslov', 'Kapitalna dobit')

@section('alati')
    @include('porezi._period')
@endsection

@section('knjiga')
    <div class="row g-4">
        <div class="col-12 order-2">
            @if ($ukupno === 0)
                <div class="prazno">
                    <p class="mb-2">Nema kupovina ni prodaja u periodu „{{ $period->naziv() }}”.</p>
                    <a href="{{ route('uvoz') }}">Uvezi Trading 212 izvod</a>
                </div>
            @else
                <x-tabela id="kapitalna-dobit" :tabela="$tabela" :redovi="$redovi" :ukupno="$ukupno" klasa="table-sm align-middle">
                    <x-slot:telo>
                        @foreach ($redovi as $red)
                            @php($prodaja = $red->tip === TipTransakcije::Prodaja)
                            <tr @class(['upozorenje-red' => $red->bez_kursa || $red->nedostaje])>
                                <td class="text-nowrap">
                                    @if ($prodaja && $red->alokacije->isNotEmpty())
                                        <button type="button" class="dugme-rasklopi" data-bs-toggle="collapse"
                                                data-bs-target=".alokacije-{{ $red->transakcija->id }}" aria-expanded="false"
                                                aria-label="Prikaži kupovine iz kojih je prodaja namirena">
                                            <span class="strelica" aria-hidden="true">›</span>
                                        </button>
                                    @endif
                                    <span @class(['tip-oznaka', 'tip-prodaja' => $prodaja, 'tip-kupovina' => ! $prodaja])>
                                        {{ $prodaja ? 'PRODAJA' : 'KUPOVINA' }}
                                    </span>
                                </td>
                                <td class="text-nowrap">{{ $red->vreme->format('d.m.Y H:i:s') }}</td>
                                <td class="fw-semibold" title="{{ $red->naziv }}">{{ $red->simbol }}</td>
                                <td class="broj">{{ Decimal::formatKolicina($red->kolicina) }}</td>
                                <td class="broj">{{ Decimal::format($red->cena, 4) }}</td>
                                <td>{{ $red->valuta }}</td>
                                <td class="broj">{{ $red->kurs ? Decimal::format($red->kurs, 4) : '' }}
                                    @if ($red->kurs === null)<span class="text-warning">nema kursa</span>@endif
                                </td>
                                <td class="broj">{{ Decimal::format($red->vrednost_rsd) }}</td>
                                <td class="broj">{{ $prodaja ? '' : Decimal::formatKolicina($red->preostalo) }}</td>
                                @if ($prodaja)
                                    <td class="broj">
                                        {{ Decimal::format($red->nabavna_rsd) }}
                                        @if ($red->nedostaje)
                                            <div class="small text-warning-emphasis text-wrap">bez kupovine: {{ Decimal::formatKolicina($red->nedostaje) }}</div>
                                        @endif
                                    </td>
                                    <td @class(['broj', 'dobit' => $red->dobit > Decimal::nula(), 'gubitak' => $red->dobit < Decimal::nula()])>{{ Decimal::format($red->dobit) }}</td>
                                    <td class="broj">{{ Decimal::format($red->porez) }}</td>
                                    <td class="broj fw-semibold">{{ Decimal::format($red->obaveza) }}</td>
                                @else
                                    <td colspan="4"></td>
                                @endif
                            </tr>
                            @foreach ($red->alokacije as $alokacija)
                                <tr class="collapse red-alokacija alokacije-{{ $red->transakcija->id }}">
                                    <td></td>
                                    <td class="text-nowrap" colspan="2">iz kupovine {{ $alokacija->vreme->format('d.m.Y H:i') }}</td>
                                    <td class="broj">{{ Decimal::formatKolicina($alokacija->kolicina) }}</td>
                                    <td class="broj">{{ Decimal::format($alokacija->cena, 4) }}</td>
                                    <td>{{ $alokacija->valuta }}</td>
                                    <td class="broj">{{ $alokacija->kurs ? Decimal::format($alokacija->kurs, 4) : 'nema kursa' }}</td>
                                    <td colspan="2" class="text-body-secondary">{{ $alokacija->broker_id }}</td>
                                    <td class="broj">{{ Decimal::format($alokacija->nabavna_rsd) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </x-slot:telo>
                </x-tabela>
                <p class="text-body-secondary small mt-2">
                    Nabavna vrednost prodaje se računa po FIFO redosledu: prvo se troše najstarije kupovine iste hartije, po NBS kursu na dan kupovine.
                    Strelica pored prodaje prikazuje kupovine iz kojih je namirena.
                </p>
            @endif
        </div>

        <aside class="col-12 order-1">
            <div class="row g-4">
            <div class="col-lg-4"><section class="polje-obrasca h-100" aria-labelledby="obaveza-naslov">
                <p class="polje-obrasca-oznaka" id="obaveza-naslov">Trenutni dug prema poreskoj ({{ mb_strtolower($period->naziv()) }})</p>
                <x-kucice :iznos="$zbir['trenutni_dug']" />
                <p class="text-body-secondary small mt-3 mb-0">Gubici se prebijaju sa dobicima iz istog perioda. Broj prodaja: {{ $zbir['broj_prodaja'] }}.</p>
            </section></div>

            <div class="col-lg-4"><dl class="mb-0">
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Prodajna vrednost</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['prodajna_rsd']) }}</dd>
                </div>
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Nabavna vrednost</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['nabavna_rsd']) }}</dd>
                </div>
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Ostvarena dobit / gubitak</dt>
                    <dd @class(['iznos', 'fs-6', 'dobit' => $zbir['neto_dobit'] > Decimal::nula(), 'gubitak' => $zbir['neto_dobit'] < Decimal::nula()])>{{ Decimal::format($zbir['neto_dobit']) }}</dd>
                </div>
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Zbir obaveza po prodajama</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['obaveza_po_prodajama']) }}</dd>
                </div>
                <div class="stavka d-flex justify-content-between gap-3">
                    <dt>Preostalo za prebijanje</dt>
                    <dd class="iznos fs-6">{{ Decimal::format($zbir['preostalo_za_prebijanje']) }}</dd>
                </div>
            </dl></div>

            <div class="col-lg-4"><section class="border bg-white p-3 h-100" aria-labelledby="ppdg-naslov">
                <h2 class="h6 mb-1" id="ppdg-naslov">Obrazac PPDG-3R</h2>
                <p class="small text-body-secondary">Prijava se podnosi za polugodište. Deo 4 se popunjava prodajama iz tog polugodišta.</p>

                @unless ($obaveznikPopunjen)
                    <p class="small text-warning-emphasis">Deo 2 će ostati prazan dok ne popunite <a href="{{ route('poreski-obaveznik') }}">podatke o poreskom obavezniku</a>.</p>
                @endunless

                <form method="GET" action="{{ route('izvoz.ppdg-3r') }}">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="ppdg-godina" class="form-label small mb-1">Godina</label>
                            <select id="ppdg-godina" name="godina" class="form-select form-select-sm">
                                @foreach ($godine ?: [$prijava->godina] as $godina)
                                    <option value="{{ $godina }}" @selected($godina === $prijava->godina)>{{ $godina }}.</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="ppdg-polugodiste" class="form-label small mb-1">Polugodište</label>
                            <select id="ppdg-polugodiste" name="polugodiste" class="form-select form-select-sm">
                                <option value="1" @selected($prijava->polugodiste === 1)>I (jan–jun)</option>
                                <option value="2" @selected($prijava->polugodiste === 2)>II (jul–dec)</option>
                            </select>
                        </div>
                    </div>

                    <details class="small mb-3">
                        <summary class="text-body-secondary">Šifre na obrascu</summary>
                        <p class="text-body-secondary mt-2 mb-2">Podrazumevane vrednosti nisu proverene u šifarniku Poreske uprave. Proverite ih pre podnošenja.</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="vrsta_prijave" class="form-label mb-1">1.1 Vrsta prijave</label>
                                <input id="vrsta_prijave" name="vrsta_prijave" class="form-control form-control-sm" inputmode="numeric" value="{{ config('porezi.ppdg3r.vrsta_prijave') }}">
                            </div>
                            <div class="col-6">
                                <label for="osnov_za_prijavu" class="form-label mb-1">1.1a Osnov</label>
                                <input id="osnov_za_prijavu" name="osnov_za_prijavu" class="form-control form-control-sm" inputmode="numeric" value="{{ config('porezi.ppdg3r.osnov_za_prijavu') }}">
                            </div>
                        </div>
                    </details>

                    <button type="submit" class="btn btn-primary btn-sm w-100">Preuzmi PPDG-3R (PDF)</button>
                </form>
            </section></div>
            </div>
        </aside>
    </div>
@endsection
