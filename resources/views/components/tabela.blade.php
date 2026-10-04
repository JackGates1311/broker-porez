{{--
    Tabela sa globalnom pretragom i sortiranjem (app/Support/Tabela). Pretraga i sort se
    rade na serveru; ovde su samo linkovi i forma. Prekidač "Sortiranje po više kolona" (?vise=1)
    menja zaglavlja tako da klik dodaje kolonu u višestruki sort.

    Pretraga dok se kuca i klikovi na linkove tabele (sort, reset, strane) preuzimaju stranu u
    pozadini (resources/js/dashboard.ts) i menjaju samo delove označene sa data-tabela-osvezi,
    bez ponovnog učitavanja. Bez JS-a sve radi kao obični linkovi i GET forma.

    Slotovi: $telo (sopstveni <tr> redovi umesto podrazumevanih), $podnozje (<tr> za tfoot),
    $alati (dodatna dugmad u kontrolnoj traci).
--}}
@props([
    'tabela',
    'redovi',
    'ukupno' => null,
    'id' => 'tabela',
    'klasa' => '',
    'prazno' => 'Nema podataka.',
    'placeholder' => 'Pretraga (simbol, datum, iznos...)',
])

@php
    /** @var \App\Support\Tabela\Tabela $tabela */
    $stanje = $tabela->stanje();
    $paginirano = $redovi instanceof \Illuminate\Contracts\Pagination\Paginator;
    $broj = $redovi instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $redovi->total() : count($redovi);
@endphp

<div data-tabela="{{ $id }}">
<div {{ $attributes->class('tabela-kontrole-wrapper mb-3') }}>
    {{-- Inline kontrolna traka (filteri, tagovi, alati levo - pretraga desno) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 bg-light border rounded-top">
        
        {{-- Leva strana: Aktivni sort tagovi, statistika i alati --}}
        <div class="d-flex flex-wrap align-items-center gap-2 small" data-tabela-osvezi="stanje">
            @if ($stanje->sort !== [])
                <span class="text-body-secondary fw-medium">Sortirano:</span>
                @foreach ($stanje->sort as $i => [$kljuc, $smer])
                    <a href="{{ $stanje->urlBezSorta($kljuc) }}" class="sort-oznaka badge bg-white text-dark border text-decoration-none px-2 py-1 d-inline-flex align-items-center gap-1" title="Ukloni iz sortiranja">
                        @if (count($stanje->sort) > 1)<span class="badge bg-secondary rounded-pill text-white" style="font-size: 0.65rem;">{{ $i + 1 }}</span>@endif
                        {{ $tabela->kolona($kljuc)?->naslov }} {{ $smer === 'asc' ? '↑' : '↓' }}
                        <span aria-hidden="true" class="text-danger fw-bold">×</span>
                    </a>
                @endforeach
            @endif

            <span class="text-body-secondary">
                @if ($stanje->pretraga !== '' && $ukupno !== null)
                    Pronađeno <strong>{{ $broj }}</strong> od {{ $ukupno }}
                @else
                    Redova: <strong>{{ $broj }}</strong>
                @endif
            </span>

            {{-- Reset filtera kao kompaktna ikonica sa tooltipom --}}
            @if ($stanje->jeIzmenjeno())
                <a href="{{ $stanje->urlReseta() }}" class="btn btn-sm btn-outline-secondary border-0 p-1 lh-1 text-danger" title="Poništi pretragu i sortiranje">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                    </svg>
                </a>
            @endif

            <a href="{{ $stanje->urlViseKolona() }}" data-fokus="vise" role="switch" aria-checked="{{ $stanje->viseKolona ? 'true' : 'false' }}"
               class="form-check form-switch mb-0 text-decoration-none text-body-secondary d-inline-flex align-items-center gap-1"
               title="Kada je uključeno, klik na zaglavlje dodaje kolonu u sortiranje umesto da je zameni">
                <input type="checkbox" class="form-check-input mt-0 pe-none" tabindex="-1" aria-hidden="true" @checked($stanje->viseKolona)>
                <span>Sortiranje po više kolona</span>
            </a>

            {{ $alati ?? '' }}
        </div>

        {{-- Desna strana: Kompaktno polje za pretragu --}}
        <form method="GET" class="tabela-pretraga ms-auto" role="search" style="width: 260px;" data-pretraga-uzivo="2">
            <div hidden data-tabela-osvezi="polja">
                @foreach ($stanje->skrivenaPolja() as $ime => $vrednost)
                    <input type="hidden" name="{{ $ime }}" value="{{ $vrednost }}">
                @endforeach
            </div>
            <label for="{{ $id }}-q" class="visually-hidden">Pretraga tabele</label>
            <div class="input-group input-group-sm">
                <input type="search" id="{{ $id }}-q" name="q" value="{{ $stanje->pretraga }}" class="form-control"
                       maxlength="{{ \App\Support\Tabela\StanjeTabele::MAKS_PRETRAGA }}" placeholder="{{ $placeholder }}" autocomplete="off">
                <button type="submit" class="btn btn-outline-primary px-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
    
    {{-- Suptilno uputstvo za sortiranje ispod zaglavlja kontrole --}}
    <div class="px-1 pt-1" data-tabela-osvezi="uputstvo">
        <small class="text-body-secondary" style="font-size: 0.75rem;">
            @if ($stanje->viseKolona)
                💡 Klik na zaglavlje dodaje kolonu u sortiranje; ponovni klik obrće smer, treći je uklanja.
            @else
                💡 Klik na zaglavlje sortira po koloni. Za sortiranje po više kolona uključite prekidač iznad.
            @endif
        </small>
    </div>
</div>

<div class="tabela-rezultati" data-tabela-osvezi="rezultati">
@if ($broj === 0)
    <div class="prazno text-center py-5 border rounded bg-white">
        @if ($stanje->pretraga !== '')
            <p class="mb-2 text-muted">Nijedan red ne odgovara pretrazi „<strong>{{ $stanje->pretraga }}</strong>”.</p>
            <a href="{{ $stanje->urlReseta() }}" class="btn btn-sm btn-outline-primary">Poništi pretragu</a>
        @else
            <p class="text-muted mb-0">{{ $prazno }}</p>
        @endif
    </div>
@else
    {{-- Bez Bootstrap-ove overflow-hidden: !important bi pregazio overflow: auto iz .tabela-omot i ugasio skrol. --}}
    <div class="tabela-omot shadow-sm rounded border bg-white">
        <table class="table table-hover align-middle mb-0 tabela-knjiga {{ $klasa }}" id="{{ $id }}">
            <thead class="table-light">
                <tr>
                    @foreach ($tabela->kolone() as $kolona)
                        @php($prioritet = $tabela->prioritet($kolona->kljuc))
                        <th scope="col" @class([$kolona->cssZaglavlja(), 'sortirano' => $prioritet !== null])
                            @if ($prioritet && $prioritet[0] === 1) aria-sort="{{ $prioritet[1] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                            @if ($kolona->jeSortabilna())
                                <a href="{{ $tabela->urlSortiranja($kolona->kljuc) }}" data-fokus="sort-{{ $kolona->kljuc }}" class="sort-link text-dark text-decoration-none d-flex align-items-center justify-content-between gap-1"
                                   title="{{ $stanje->viseKolona ? 'Dodaj u sortiranje po više kolona' : 'Sortiraj po ovoj koloni' }}">
                                    <span @class(['visually-hidden' => $kolona->imaSkrivenNaslov()]) class="fw-semibold">{{ $kolona->naslov }}</span>
                                    <span class="sort-strelica text-muted" aria-hidden="true" style="font-size: 0.8rem;">@if ($prioritet){{ $prioritet[1] === 'asc' ? '▲' : '▼' }}@if (count($tabela->efektivniSort()) > 1)<sup class="text-primary fw-bold">{{ $prioritet[0] }}</sup>@endif @else ↕ @endif</span>
                                </a>
                            @else
                                <span @class(['visually-hidden' => $kolona->imaSkrivenNaslov()]) class="fw-semibold">{{ $kolona->naslov }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if (isset($telo))
                    {{ $telo }}
                @else
                    @foreach ($redovi as $red)
                        <tr @class($tabela->klasaZa($red))>
                            @foreach ($tabela->kolone() as $kolona)
                                <td @class([$kolona->css()])>{{ $kolona->prikazi($red) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
            @isset($podnozje)
                <tfoot class="table-light">{{ $podnozje }}</tfoot>
            @endisset
        </table>
    </div>

    @if ($paginirano)
        <div class="mt-3 d-flex flex-wrap align-items-center justify-content-between gap-2 px-1">
            <div class="text-muted small">
                Prikazano <strong>{{ $redovi->firstItem() ?? 0 }}</strong> do <strong>{{ $redovi->lastItem() ?? 0 }}</strong> od ukupno <strong>{{ $redovi->total() }}</strong> rezultata
            </div>
            <div>
                {{ $redovi->links('pagination.stranice') }}
            </div>
        </div>
    @endif
@endif
</div>
</div>
