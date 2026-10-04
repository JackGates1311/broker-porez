@extends('layouts.app')

@section('naslov', 'Proverite email')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Proverite email</h1>

            <p class="text-body-secondary text-center">
                Ako postoji nalog za <strong>{{ $email }}</strong>, poslali smo link za postavljanje nove lozinke.
                Link važi {{ \App\Services\ResetLozinkeService::TRAJANJE_LINKA_MINUTA }} minuta.
            </p>

            <a href="{{ route('prijava') }}" class="btn btn-primary w-100">Nazad na prijavu</a>

            <hr class="my-4">

            <form method="POST" action="{{ route('lozinka.zaboravljena') }}" class="text-center">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <span class="text-body-secondary">Niste dobili mejl?</span>
                <button type="submit" class="btn btn-link p-0 align-baseline"
                        @if (session('poslato')) data-odbrojavanje="60" @endif>
                    Pošalji ponovo
                </button>
            </form>

            @error('throttle')
                <div class="text-danger small text-center mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>
@endsection
