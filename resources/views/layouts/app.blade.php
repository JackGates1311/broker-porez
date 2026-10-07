<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@hasSection('naslov')@yield('naslov') – @endif{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-md bg-body border-bottom">
            <div class="@yield('kontejner', 'container')">
                <a class="navbar-brand fw-semibold" href="{{ url('/') }}">{{ config('app.name') }}</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#glavnaNavigacija"
                        aria-controls="glavnaNavigacija" aria-expanded="false" aria-label="Prikaži navigaciju">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="glavnaNavigacija">
                    <ul class="navbar-nav ms-auto align-items-md-center gap-md-3">
                        @auth
                            <li class="nav-item">
                                <span class="navbar-text">Dobrodošli <strong>{{ auth()->user()->korisnicko_ime }}</strong></span>
                            </li>
                            <li class="nav-item">
                                <form method="POST" action="{{ route('odjava') }}">
                                    @csrf
                                    <button type="submit" class="dugme-odjava" title="Odjava" aria-label="Odjava">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                                        </svg>
                                    </button>
                                </form>
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
    </body>
</html>
