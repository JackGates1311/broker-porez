{{--
    Vrednost upisana u kućice |_|, kao na papirnom obrascu. Kućice su ćelije male tabele, jer
    dompdf ne poravnava pouzdano inline-block elemente.
    $vrednost: znakovi redom, bez razdvajača; $grupe: broj kućica po grupi (podrazumevano jedna grupa
    dužine vrednosti); $legenda: natpis ispod svake grupe (дд, мм, гггг); $spoj: znak između grupa
    umesto razmaka (tekući račun 3-13-2); $razmaci: širina razmaka pre grupe 1, 2, … u mm;
    $cela: cela vrednost u jednu kućicu (redni broj 10, 11, …); $visinaKucica: visina kućica u mm, kad se
    razlikuje od $kv (ЈМБГ/ПИБ polja na PPDG-3R). Višak znakova se ne crta.
--}}
@php
    $znakovi = ($cela ?? false) ? [(string) $vrednost] : mb_str_split((string) ($vrednost ?? ''));
    $grupe ??= [max(1, count($znakovi))];
    $legenda ??= null;
    $spoj ??= null;
    $razmaci ??= [];
    // Niska visina reda, da visinu kućice određuje $visinaKucica, a ne slova u njoj.
    $visinaKucica = isset($visinaKucica) ? ' style="height: '.$visinaKucica.'mm; line-height: 2.5mm"' : '';
    $kucice = '';
    $natpisi = '';
    $i = 0;
    foreach ($grupe as $g => $n) {
        if ($g > 0) {
            $sirina = $razmaci[$g - 1] ?? ($spoj === null ? $kr : 1.4);
            $kucice .= '<td class="r" style="width: '.$sirina.'mm">'.e($spoj ?? '').'</td>';
            $natpisi .= '<td class="r l" style="width: '.$sirina.'mm"></td>';
        }
        for ($j = 0; $j < $n; $j++, $i++) {
            $znak = $znakovi[$i] ?? '';
            $kucice .= '<td'.($j === $n - 1 ? ' class="kraj"' : '').$visinaKucica.'>'.(trim($znak) === '' ? '&nbsp;' : e($znak)).'</td>';
        }
        $natpisi .= '<td class="l" colspan="'.$n.'">'.e($legenda[$g] ?? '').'</td>';
    }
@endphp
<table class="kt"@if ($legenda && isset($legendaPomak)) style="margin-top: {{ $legendaPomak }}mm"@endif><tr>{!! $kucice !!}</tr>@if ($legenda)<tr>{!! $natpisi !!}</tr>@endif</table>
