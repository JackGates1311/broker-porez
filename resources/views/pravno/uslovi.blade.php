@extends('layouts.app')

@section('naslov', 'Uslovi korišćenja')

@section('sadrzaj')
    <article class="pravni-tekst mx-auto">
        <h1 class="knjiga-naslov">Uslovi korišćenja</h1>
        <p class="vazi-od">Verzija {{ config('pravno.verzija') }}, važi od {{ \App\Support\Pravno::vaziOdPrikaz() }}</p>

        @include('pravno._uslovi')
    </article>
@endsection
