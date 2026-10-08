{{-- Traka koraka čarobnjaka ručnog uvoza. Završeni koraci (do $dostupno) su linkovi. --}}
@php
    $koraci = [
        1 => ['Fajl', null],
        2 => ['Format', 'uvoz.rucni.format'],
        3 => ['Kolone', 'uvoz.rucni.kolone'],
        4 => ['Akcije', 'uvoz.rucni.akcije'],
        5 => ['Pregled i uvoz', 'uvoz.rucni.pregled'],
    ];
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <p class="small text-body-secondary mb-2">
            Uvoz iz fajla <strong class="text-body">{{ $carobnjak->nazivFajla() }}</strong>
            @if ($carobnjak->sablonNaziv())
                po šablonu <strong class="text-body">{{ $carobnjak->sablonNaziv() }}</strong>
            @endif
        </p>
        <ol class="koraci-uvoza" aria-label="Koraci uvoza">
            @foreach ($koraci as $broj => [$naziv, $ruta])
                <li @class(['aktivan' => $broj === $korak, 'zavrsen' => $broj < $korak || ($ruta !== null && $broj <= $dostupno && $broj !== $korak)])
                    @if ($broj === $korak) aria-current="step" @endif>
                    @if ($ruta !== null && $broj !== $korak && $broj <= $dostupno)
                        <a href="{{ route($ruta) }}"><span class="broj">{{ $broj }}</span>{{ $naziv }}</a>
                    @else
                        <span><span class="broj">{{ $broj }}</span>{{ $naziv }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <form method="POST" action="{{ route('uvoz.rucni.odustani') }}" data-potvrda="Prekinuti uvoz? Fajl se briše, a sačuvani šabloni ostaju.">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">Odustani</button>
    </form>
</div>
