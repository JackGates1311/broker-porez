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
                    <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                        @auth
                            <li class="nav-item">
                                <span class="navbar-text">{{ auth()->user()->korisnicko_ime }}</span>
                            </li>
                            <li class="nav-item">
                                <form method="POST" action="{{ route('odjava') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Odjava</button>
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
