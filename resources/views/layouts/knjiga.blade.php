@extends('layouts.app')

@section('kontejner', 'container-fluid knjiga px-3 px-lg-4')
@section('sirina-poruke', '')

@section('sadrzaj')
    @if (session('greske_uvoza'))
        <div class="alert alert-warning" role="alert">
            <p class="mb-1 fw-semibold">Neki redovi nisu uvezeni:</p>
            <ul class="mb-0 small">
                @foreach (array_slice(session('greske_uvoza'), 0, 20) as $greska)
                    <li>{{ $greska }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <h1 class="knjiga-naslov">@yield('naslov')</h1>
        @yield('alati')
    </div>

    <nav aria-label="Delovi poreske knjige">
        <ul class="nav knjiga-tabovi mb-4">
            @foreach ([
                'pocetna' => 'Pregled',
                'kapitalna-dobit' => 'Kapitalna dobit',
                'dividende' => 'Dividende',
                'uvoz' => 'Uvoz i transakcije',
                'poreski-obaveznik' => 'Poreski obaveznik',
            ] as $ruta => $naziv)
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs($ruta, "{$ruta}.*")) active @endif"
                       @if (request()->routeIs($ruta, "{$ruta}.*")) aria-current="page" @endif
                       href="{{ route($ruta, in_array($ruta, ['uvoz', 'poreski-obaveznik'], true) ? [] : array_filter(['period' => request('period')])) }}">{{ $naziv }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    @yield('knjiga')
@endsection
