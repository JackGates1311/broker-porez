{{--
    Polje ⌊ vrednost ⌋ širine $sirina mm. Duža vrednost dobija manja slova da stane u polje
    (procena: prosečan znak ≈ 0,55 em), ali ne manja od 5 pt.
--}}
@php
    $tekst = (string) ($vrednost ?? '');
    $duzina = mb_strlen($tekst);
    $velicina = $duzina === 0 ? $fk : min($fk, max(5, ($sirina - 1.4) / ($duzina * 0.55 * 0.3528)));
@endphp
<table class="pt" style="width: {{ $sirina }}mm"><tr><td @class(['desno' => $desno ?? false]) style="font-size: {{ round($velicina, 2) }}pt">@if ($duzina){{ $tekst }}@else&nbsp;@endif</td></tr></table>
