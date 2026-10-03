{{-- Vrednost upisana u kućice, kao na papirnom obrascu. Tačka razdvaja grupe (dd.mm.gggg). --}}
<span class="kucice">
@php($grupe = explode('.', (string) $vrednost))
@foreach ($grupe as $i => $grupa)
    @php($znakovi = mb_str_split($grupa))
    @foreach ($znakovi as $j => $znak)<span @class(['kucica', 'poslednja' => $j === count($znakovi) - 1])>{{ $znak }}</span>@endforeach
    @if ($i < count($grupe) - 1)<span class="razmak"></span>@endif
@endforeach
</span>
