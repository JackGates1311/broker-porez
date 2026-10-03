@props(['iznos', 'valuta' => 'RSD'])

@php
    $tekst = \App\Support\Decimal::format($iznos);
    $znakovi = mb_str_split($tekst);
@endphp

{{-- Svaka grupa cifara je niz kućica; tačka i zarez prekidaju niz, kao na obrascu. --}}
<div {{ $attributes->merge(['class' => 'kucice']) }} role="img" aria-label="{{ $tekst }} {{ $valuta }}">
    @foreach ($znakovi as $i => $znak)
        @php($cifra = ctype_digit($znak) || $znak === '-')
        <span @class([
            'kucica' => $cifra,
            'razdelnik' => ! $cifra,
            'kraj-grupe' => $cifra && ! (ctype_digit($znakovi[$i + 1] ?? '')),
        ]) aria-hidden="true">{{ $znak }}</span>
    @endforeach
    <span class="valuta" aria-hidden="true">{{ $valuta }}</span>
</div>
