@extends('layouts.app')

@section('naslov', 'Korisničko ime')

@section('sadrzaj')
    <div class="card shadow-sm auth-kartica mx-auto">
        <div class="card-body p-4">
            <h1 class="h4 mb-2 text-center">Izaberite korisničko ime</h1>
            <p class="text-body-secondary text-center mb-4">
                Predložili smo ime na osnovu vaše email adrese. Zadržite ga ili upišite drugo.
                Kasnije ga možete promeniti u delu „Moj nalog”.
            </p>

            <form method="POST" action="{{ route('nalog.korisnicko-ime') }}" class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-4">
                    @include('auth._korisnicko-ime', ['vrednost' => $korisnik->korisnicko_ime, 'autofocus' => true])
                </div>

                <button type="submit" class="btn btn-primary w-100">Nastavi</button>
            </form>
        </div>
    </div>
@endsection
