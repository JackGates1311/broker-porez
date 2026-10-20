@extends('layouts.app')

@section('naslov', 'Politika privatnosti')

@section('sadrzaj')
    <article class="pravni-tekst mx-auto">
        <h1 class="knjiga-naslov">Politika privatnosti</h1>
        <p class="vazi-od">Verzija {{ config('pravno.verzija') }}, važi od {{ \App\Support\Pravno::vaziOdPrikaz() }}</p>

        @include('pravno._politika')
    </article>
@endsection
