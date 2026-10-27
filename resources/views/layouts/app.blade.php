<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@hasSection('naslov')@yield('naslov') – @endif{{ config('app.name') }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#0b2746">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-md bg-body border-bottom">
            <div class="@yield('kontejner', 'container')">
                <a class="navbar-brand marka" href="{{ url('/') }}" aria-label="{{ config('app.name') }}">
                    <img src="{{ asset('images/logo/znak-64.png') }}" width="32" height="32" alt="">
                    <span aria-hidden="true"><span class="marka-broker">Broker</span><span class="marka-porez">Porez</span></span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#glavnaNavigacija"
                        aria-controls="glavnaNavigacija" aria-expanded="false" aria-label="Prikaži navigaciju">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="glavnaNavigacija">
                    <ul class="navbar-nav ms-auto align-items-md-center gap-md-3">
                        @auth
                            <li class="nav-item dropdown korisnicki-meni">
                                <button type="button" class="dugme-korisnik dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                                        aria-label="Meni naloga {{ auth()->user()->korisnicko_ime }}">
                                    <span class="avatar" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                                        </svg>
                                    </span>
                                    <span class="ime">{{ auth()->user()->korisnicko_ime }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li class="px-3 pt-1 pb-2">
                                        <div class="fw-semibold">{{ auth()->user()->korisnicko_ime }}</div>
                                        <div class="small text-body-secondary text-break">{{ auth()->user()->email }}</div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('nalog') }}">Moj nalog i podaci</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('pravno.politika') }}">Politika privatnosti</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pravno.uslovi') }}">Uslovi korišćenja</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('odjava') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item stavka-odjava">Odjavi se</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('prijava')) active @endif" href="{{ route('prijava') }}">Prijava</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('registracija')) active @endif" href="{{ route('registracija') }}">Registracija</a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <main class="flex-grow-1 py-5">
            <div class="@yield('kontejner', 'container')">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show @yield('sirina-poruke', 'auth-kartica mx-auto')" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zatvori"></button>
                    </div>
                @endif

                @yield('sadrzaj')
            </div>
        </main>

        <footer class="podnozje border-top">
            <div class="@yield('kontejner', 'container') d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <span>
                    © {{ now()->year }} BrokerPorez.
                    <a href="{{ route('pravno.politika') }}">Podaci se čuvaju u EU i obrađuju u skladu sa GDPR-om i Zakonom o zaštiti podataka o ličnosti.</a>
                </span>
                <nav aria-label="Pravne informacije">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="{{ route('pravno.politika') }}">Politika privatnosti</a></li>
                        <li class="list-inline-item"><a href="{{ route('pravno.uslovi') }}">Uslovi korišćenja</a></li>
                    </ul>
                </nav>
            </div>
        </footer>

        @if (auth()->user()?->jeInsajder() && auth()->user()->jeVerifikovan())
            @include('layouts._podrska')
        @endif
    </body>
</html>
