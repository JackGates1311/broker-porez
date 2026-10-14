{{--
    Kućice obrasca PP OPO, nacrtane po meri izmerenoj na zvaničnom obrascu (mm). Na obrascu su to
    znakovi "|" i "_" iz Calibrija; Carlito ima iste širine, ali drugačiji crtež tih znakova (crte
    vire ispod podvlake), pa se crtaju linijama.

    $grupe: broj kućica po grupi; $vrednost: znakovi redom (umesto podvlake);
    $tip: 'crta' (|_|_|, podvlaka u svakoj kućici) ili 'razmak' (datumi u Delu 1: podvlaka kroz celu
    grupu); $spoj: '' (||), ' ', '-' ili '   ' između grupa; $legenda + $legendaX: natpisi ispod,
    na mestu u mm od leve ivice prve crte; $duzina: polje "|____|" sa razmakom između crta u mm;
    $gore / $desno: pomeraj crteža u mm (natpisi ostaju na mestu).
--}}
@php
    $H = 3.13;          // visina crte
    $D = 0.27;          // debljina crte
    $T = 0.21;          // debljina podvlake
    $tip ??= 'crta';
    $spoj ??= '';
    $legenda ??= null;
    $legendaX ??= null;
    $duzina ??= null;
    $korak = $tip === 'razmak' ? 3.26 : 3.415;
    $razmakGrupa = ['' => 1.65, ' ' => 2.46, '-' => 2.71, '   ' => 4.07][$spoj] ?? 1.65;
    $znakovi = mb_str_split((string) ($vrednost ?? ''));

    // Položaji crta (centar, mm od leve ivice prve crte) i podvlake.
    $crte = [];
    $podvlake = [];
    $slova = [];
    $crtice = [];
    if ($duzina !== null) {
        $crte = [$D / 2, $D / 2 + $duzina];
        $podvlake[] = [$D / 2 + 0.85, $D / 2 + $duzina - ($krajPodvlake ?? 0.8), $podignuto ?? 0];
    } else {
        $grupe ??= [max(1, count($znakovi))];
        $x = $D / 2;
        $i = 0;
        foreach ($grupe as $g => $n) {
            if ($g > 0) {
                if ($spoj === '-') {
                    $crtice[] = $x + 0.97;
                }
                $x += $razmakGrupa;
            }
            $prva = $x;
            for ($j = 0; $j <= $n; $j++) {
                $crte[] = $x;
                if ($j < $n) {
                    if ($tip === 'crta') {
                        $podvlake[] = [$x + 0.85, $x + $korak - 0.80, 0];
                    }
                    if (($znakovi[$i] ?? '') !== '' && trim($znakovi[$i]) !== '') {
                        $slova[] = [$x + $korak / 2, $znakovi[$i]];
                    }
                    $i++;
                    $x += $korak;
                }
            }
            if ($tip === 'razmak') {
                $podvlake[] = [$prva + 0.89, $x - 0.76, 0];
            }
        }
    }
    $sirina = max($crte) + $D / 2;
@endphp
<div class="kd" style="width: {{ round($sirina, 2) }}mm; top: {{ -($gore ?? 0) }}mm; left: {{ $desno ?? 0 }}mm">
    @foreach ($crte as $c)<i style="left: {{ round($c - $D / 2, 3) }}mm; top: 0; width: {{ $D }}mm; height: {{ $H }}mm"></i>@endforeach
    @foreach ($podvlake as [$od, $do, $gore])<i style="left: {{ round($od, 3) }}mm; top: {{ round($H - $T - $gore, 3) }}mm; width: {{ round($do - $od, 3) }}mm; height: {{ $T }}mm"></i>@endforeach
    @foreach ($crtice as $c)<i style="left: {{ round($c, 3) }}mm; top: {{ round($H - 1.39 - 0.26, 3) }}mm; width: 0.85mm; height: 0.26mm"></i>@endforeach
    @foreach ($slova as [$c, $znak])<b style="left: {{ round($c - 1.5, 3) }}mm">{{ $znak }}</b>@endforeach
</div>
@if ($legenda && $legendaX)
    <div class="legx">@foreach ($legenda as $g => $natpis)<span style="left: {{ $legendaX[$g] }}mm">{{ $natpis }}</span>@endforeach&nbsp;</div>
@endif
