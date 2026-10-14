@use('App\Support\Decimal')
@use('App\Support\Drzave')
{{--
    Obrazac PPDG-3R, raspored kao na zvaničnom obrascu: strana 1 (Deo 1–3), strana 2 (Deo 4, devet
    blokova sa po tri reda sticanja; za više prodaja se ponavlja), strana 3 (Deo 5–9 i potpis).
    PDF sadrži samo obrazac; obračun je na tabu Kapitalna dobit. Raspored blokova: RasporedDela4.

    Mere su izmerene na skenu obrasca, u mm. $s() i $v() pretvaraju meru ivica-do-ivice u širinu,
    odnosno visinu sadržaja ćelije (dompdf dodaje padding i ivicu); visina se zadaje ćeliji, jer
    dompdf ne poštuje visinu reda.
--}}
@php
    $font = 'serif';
    $margine = '8.6mm 11.2mm 4mm 13mm';
    $fs = 7.65;
    $k = 3.82;
    $kv = 3.9;
    // Grupa sa natpisom (дд мм гггг) spušta se za visinu natpisa, da kućice budu u ravni sa susednim.
    $legendaPomak = 2.5;
    $kr = 0.6;
    $fk = 8.5;
    $fl = 7;
    $flr = 0;
    $fls = 0.6;
    $s = fn (float $mm) => 'width: '.($mm - 1.3).'mm;';
    // Ćelije bez unutrašnje margine (th.rb).
    $sb = fn (float $mm) => 'width: '.($mm - 0.3).'mm;';
    $v = fn (float $mm) => 'height: '.($mm - 0.3).'mm;';
    $iznos = fn ($x) => $x === null ? '' : Decimal::format($x);
    $kolicina = fn ($x) => $x === null ? '' : Decimal::formatKolicina($x);
    $tipovi = [
        1 => 'Резидентно физичко лице',
        2 => 'Предузетник који порез плаћа на паушално утврђен приход',
        3 => 'Нерезидентно физичко лице које има боравиште у Републици',
        4 => 'Нерезидентно физичко лице које нема боравиште у Републици',
    ];
    $tip = $obaveznik->tip_obaveznika;
@endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>ППДГ-3Р {{ $period->kod }}</title>
    @include('obrasci._stil')
    <style>
        .naslov { width: 272.8mm; border-collapse: collapse; }
        .naslov td { border-top: 0.75pt solid #000; border-bottom: 0.75pt solid #000; height: 3.2mm; padding: 0 0.6mm; line-height: 1; vertical-align: middle; }
        .naslov td.prva { border-left: 0.75pt solid #000; }
        .naslov td.poslednja { border-right: 0.75pt solid #000; text-align: right; }
        /* Strana 3 je na skenu sabijena (uža za oko 11 mm); ovde je široka i poravnata kao Deo 4,
           kolone su srazmerno proširene, a tekst razvučen za isto toliko (oko 4 %), da se naslov dela
           završava ispod „Из…” u poslednjoj koloni kao na obrascu. Datumi imaju veći razmak između grupa. */
        .strana3 .deo { letter-spacing: 0.14pt; }
        .strana3 th { letter-spacing: 0.12pt; }
        /* „Редни број” стаје у два реда као на обрасцу. */
        table.mreza th.rb { padding: 0; line-height: 0.95; }
        /* Naslov dela je na skenu poravnat sa levom ivicom tabele i bliže joj. */
        .deo { padding-left: 0; margin-left: -0.2mm; position: relative; top: 0.4mm; }
        .potpis { margin: 6.4mm 0 0 199.4mm; width: 69mm; border-top: 0.75pt solid #000; padding-top: 0.6mm; text-align: center; }
        .kvadrat { display: inline-block; width: 3.6mm; height: 3.6mm; border: 0.75pt solid #000; vertical-align: middle; }
        .spajalica { width: 4.2mm; height: 4.2mm; vertical-align: middle; margin-left: 0.6mm; }
    </style>
</head>
<body>
    {{-- ===== Strana 1: Deo 1, 2 i 3 ===== --}}
    <table class="naslov">
        <tr>
            <td class="prva">Пореска пријава за утврђивање пореза на капиталне добитке</td>
            <td class="poslednja">Образац ППДГ-3Р</td>
        </tr>
    </table>

    <div class="deo" style="margin-top: 2mm">Део 1. Подаци о пријави</div>
    <table class="mreza">
        <tr>
            <th style="{{ $s(12.1) }}{{ $v(10.3) }}">1.1 Врста пријаве</th>
            <th style="{{ $s(20.9) }}">1.1а Основ за пријаву</th>
            <th style="{{ $s(48.9) }}">1.2 Датум остваривања прихода/дела прихода</th>
            <th style="{{ $s(48.9) }}">1.3 Датум доспелости за подношење пореске пријаве</th>
            <th style="{{ $s(53) }}">1.4 Датум и начин<br>подношења пријаве</th>
            <th style="{{ $s(28.4) }}">1.5 Измена/ Сторнирање пријаве</th>
            <th style="{{ $s(60.6) }}">1.5а Идентификациони број пријаве</th>
        </tr>
        <tr>
            <td style="{{ $v(11.1) }}">@include('obrasci._kucice', ['vrednost' => $sifre['vrsta_prijave']])</td>
            <td>@include('obrasci._kucice', ['vrednost' => $sifre['osnov_za_prijavu']])</td>
            <td>@include('obrasci._kucice', ['vrednost' => $period->do->format('dmY'), 'grupe' => [2, 2, 4, 1], 'legenda' => ['дд', 'мм', 'гггг', ''], 'razmaci' => [$kr, $kr, 4.5]])</td>
            <td>@include('obrasci._datum', ['datum' => $period->datumDospelosti()])</td>
            <td>@include('obrasci._kucice', ['vrednost' => now('Europe/Belgrade')->format('dmY'), 'grupe' => [2, 2, 4, 1], 'legenda' => ['дд', 'мм', 'гггг', 'е/п'], 'razmaci' => [$kr, $kr, 4.5]])</td>
            <td>@include('obrasci._kucice', ['vrednost' => null])</td>
            <td>@include('obrasci._polje', ['vrednost' => null, 'sirina' => 52.8])</td>
        </tr>
    </table>

    <div class="deo" style="margin-top: 2.4mm">Део 2. Подаци о пореском обвезнику</div>
    <table class="mreza">
        @foreach ([
            ['2.1 Тип пореског обвезника', 'polje', $tipovi[$tip] ?? null],
            ['2.2 Порески идентификациони број (ЈМБГ/ЕБС/ПИБ)', 'kucice', $obaveznik->pib],
            ['2.3 Име и презиме пореског обвезника', 'polje', $obaveznik->ime_prezime],
            ['2.4 Пребивалиште/боравиште/седиште/општина остваривања прихода', 'polje', $obaveznik->prebivaliste],
            ['2.5 Адреса пореског обвезника', 'polje', $obaveznik->adresa],
            ['2.6 Телефон', 'polje', $obaveznik->telefon],
            ['2.7 Електронска адреса', 'polje', $obaveznik->email],
            ['2.8 ЈМБГ подносиоца пријаве', 'kucice', $obaveznik->jmbg_podnosioca],
            ['2.9 Земља резидентства', 'polje', Drzave::zaObrazac($obaveznik->zemlja_rezidentstva)],
            ['2.10 ЈМБГ/ПИБ пореског пуномоћника', 'kucice', $obaveznik->pib_punomocnika],
        ] as [$natpis, $vrsta, $vrednost])
            <tr>
                <td class="levo" style="{{ $s(109.8) }}{{ $v(7.01) }}">{{ $natpis }}</td>
                <td style="{{ $s(163) }}">
                    @if ($vrsta === 'kucice')
                        {{-- Kućice su kvadratne (oko 4,1 × 4,1 mm) i pomerene ulevo od sredine polja, kao na obrascu. --}}
                        <div class="levo" style="margin-left: 48.6mm">@include('obrasci._kucice', ['vrednost' => $vrednost, 'grupe' => [13], 'visinaKucica' => 3.75])</div>
                    @else
                        @include('obrasci._polje', ['vrednost' => $vrednost, 'sirina' => 155])
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <div class="deo" style="margin-top: 2.6mm">Део 3. Подаци за утврђивање пореза код преноса права/удела/дигиталне имовине</div>
    <table class="mreza">
        <tr>
            <th class="rb" style="{{ $sb(12.1) }}{{ $v(6.9) }}">3.1 Редни број</th>
            <th style="{{ $s(40.6) }}">3.2 Датум преноса</th>
            <th style="{{ $s(57.1) }}">3.3 Продајна цена</th>
            <th style="{{ $s(56.8) }}">3.4 Датум стицања</th>
            <th style="{{ $s(106.2) }}">3.5 Набавна цена</th>
        </tr>
        @foreach (range(1, 3) as $blok)
            @foreach (range(0, 2) as $r)
                <tr>
                    @if ($r === 0)
                        <td rowspan="3">@include('obrasci._kucice', ['vrednost' => null])</td>
                        <td rowspan="3">@include('obrasci._datum', ['datum' => null])</td>
                        <td rowspan="3">@include('obrasci._polje', ['vrednost' => null, 'sirina' => 49.3])</td>
                    @endif
                    {{-- Na zvaničnom obrascu samo prvi blok ima tri reda datuma sticanja. --}}
                    @if ($blok === 1)
                        <td style="{{ $v(6.92) }}">@include('obrasci._datum', ['datum' => null, 'bezLegende' => true])</td>
                    @elseif ($r === 0)
                        <td rowspan="3">@include('obrasci._datum', ['datum' => null, 'bezLegende' => true])</td>
                    @endif
                    <td style="{{ $v(6.92) }}{{ $r > 0 ? 'border-top: 0;' : '' }}{{ $r < 2 ? 'border-bottom: 0;' : '' }}">@include('obrasci._polje', ['vrednost' => null, 'sirina' => 97.9])</td>
                </tr>
            @endforeach
        @endforeach
    </table>

    {{-- ===== Strana 2: Deo 4, po devet blokova na strani ===== --}}
    @foreach ($strane as $strana)
        <div class="prelom"></div>
        <div class="deo" style="margin-top: 3.7mm">Део 4. Подаци за утврђивање пореза код преноса хартија од вредности/инвестиционих јединица</div>
        <table class="mreza">
            <tr>
                <th class="rb" style="{{ $sb(12.1) }}{{ $v(10.1) }}">4.1 Редни број</th>
                <th style="{{ $s(32.6) }}">4.2 Назив емитента/ инвестиционог фонда</th>
                <th style="{{ $s(40.7) }}">4.3 Датум преноса</th>
                <th style="{{ $s(24.6) }}">4.4 Број документа о преносу</th>
                <th class="rb" style="{{ $sb(24.4) }}">4.5 Број пренетих ХоВ/инвестиционих јединица</th>
                <th style="{{ $s(24.3) }}">4.6 Продајна цена</th>
                <th style="{{ $s(40.7) }}">4.7 Датум стицања</th>
                <th style="{{ $s(24.4) }}">4.8 Број документа о стицању</th>
                <th class="rb" style="{{ $sb(24.4) }}">4.9 Број стечених ХоВ/инвестиционих јединица</th>
                <th style="{{ $s(24.6) }}">4.10 Набавна цена</th>
            </tr>
            @foreach ($strana as $blok)
                @php($p = $blok->prodaja)
                @foreach ($blok->stavke as $r => $a)
                    <tr>
                        @if ($r === 0)
                            <td rowspan="3">@include('obrasci._kucice', ['vrednost' => $blok->redni_broj, 'grupe' => [1], 'cela' => true])</td>
                            {{-- 4.2: tiker hartije (INTC, NVDA…), a naziv samo ako tikera nema. --}}
                            <td rowspan="3">@include('obrasci._polje', ['vrednost' => $p ? ($p->simbol ?? $p->naziv) : null, 'sirina' => 24.5])</td>
                            <td rowspan="3">@include('obrasci._datum', ['datum' => $p?->vreme])</td>
                            <td rowspan="3">@include('obrasci._polje', ['vrednost' => $p?->transakcija->broker_transakcija_id, 'sirina' => 16.6])</td>
                            <td rowspan="3">@include('obrasci._polje', ['vrednost' => $p ? $kolicina($p->kolicina) : null, 'sirina' => 16.6, 'desno' => true])</td>
                            <td rowspan="3">@include('obrasci._polje', ['vrednost' => $p ? $iznos($p->vrednost_rsd) : null, 'sirina' => 16.6, 'desno' => true])</td>
                        @endif
                        <td style="{{ $v(6.57) }}">@include('obrasci._datum', ['datum' => $a?->vreme, 'bezLegende' => true, 'visinaKucica' => 4.1])</td>
                        <td>@include('obrasci._polje', ['vrednost' => $a?->broker_id, 'sirina' => 16.6])</td>
                        <td>@include('obrasci._polje', ['vrednost' => $a ? $kolicina($a->kolicina) : null, 'sirina' => 16.6, 'desno' => true])</td>
                        <td>@include('obrasci._polje', ['vrednost' => $a ? $iznos($a->nabavna_rsd) : null, 'sirina' => 16.6, 'desno' => true])</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    @endforeach

    {{-- ===== Strana 3: Deo 5–9 i potpis ===== --}}
    <div class="prelom"></div>
    <div class="strana3">
        @foreach ([
            ['Део 5. Подаци од значаја за остваривање права на пореско ослобођење код улагања средстава за решавање стамбеног питања обвезника', 10, 1, [
                '5.1 Редни број', '5.2 Датум улагања средстава за решавање стамбеног питања обвезника',
                '5.3 Површина за пореско ослобођење за обвезника', '5.4 Износ уложених средстава за решавање стамбеног питања обвезника']],
            ['Део 6. Подаци од значаја за остваривање права на пореско ослобођење код улагања средстава за решавање стамбеног питања обвезникове породице, односно чланова домаћинства', 13.6, 3, [
                '6.1 Редни број', '6.2 Датум улагања средстава за решавање стамбеног питања обвезникове породице, односно чланова домаћинства',
                '6.3 Површина за пореско ослобођење обвезникове породице, односно чланова домаћинства', "6.4 Износ уложених средстава за решавање стамбеног питања\nобвезникове породице, односно чланова домаћинства"]],
            ['Део 7. Подаци од значаја за остваривање права на пореско ослобођење код улагања средстава у основни капитал привредног друштва, односно капитал инвестиционог фонда', 13.6, 3, [
                '7.1 Редни број', '7.2 Датум улагања средстава у основни капитал привредног друштва, односно капитал инвестиционог фонда',
                '7.3 Износ уложених средстава у основни капитал привредног друштва', '7.4 Износ уложених средстава у капитал инвестиционог фонда']],
        ] as $i => [$naslov, $visina, $redova, $kolone])
            <div class="deo" style="margin-top: {{ $i === 0 ? '-1.8mm' : '2.15mm' }}">{{ $naslov }}</div>
            <table class="mreza">
                <tr>
                    <th class="rb" style="{{ $sb(12.1) }}{{ $v($visina) }}">{{ $kolone[0] }}</th>
                    <th style="{{ $s(45.3) }} line-height: 0.95">{{ $kolone[1] }}</th>
                    <th style="{{ $s(70.9) }} line-height: 0.95">{{ $kolone[2] }}</th>
                    <th style="{{ $s(144.3) }} line-height: 0.95">{!! nl2br(e($kolone[3])) !!}</th>
                </tr>
                @foreach (range(1, $redova) as $r)
                    <tr>
                        <td style="{{ $v(6.47) }}">@include('obrasci._kucice', ['vrednost' => null])</td>
                        <td>@include('obrasci._kucice', ['vrednost' => null, 'grupe' => [2, 2, 4], 'razmaci' => [1, 1]])</td>
                        <td>@include('obrasci._polje', ['vrednost' => null, 'sirina' => 62.3])</td>
                        <td>@include('obrasci._polje', ['vrednost' => null, 'sirina' => 136.1])</td>
                    </tr>
                @endforeach
            </table>
        @endforeach

        <div class="deo" style="margin-top: 2.15mm">Део 8. Капитални губици</div>
        <table class="mreza">
            <tr>
                <th class="rb" style="{{ $sb(12.1) }}{{ $v(13.6) }}">8.1 Редни број</th>
                <th style="{{ $s(45.3) }} line-height: 0.95">8.2 Број решења о утврђивању капиталног губитка</th>
                <th style="{{ $s(70.9) }}">8.3 Датум доношења решења</th>
                <th style="{{ $s(144.3) }}">8.4 Износ капиталног губитка</th>
            </tr>
            @foreach (range(1, 3) as $r)
                <tr>
                    <td style="{{ $v(6.47) }}">@include('obrasci._kucice', ['vrednost' => null])</td>
                    <td>@include('obrasci._polje', ['vrednost' => null, 'sirina' => 37])</td>
                    <td>@include('obrasci._kucice', ['vrednost' => null, 'grupe' => [2, 2, 4], 'razmaci' => [1, 1]])</td>
                    <td>@include('obrasci._polje', ['vrednost' => null, 'sirina' => 136.1])</td>
                </tr>
            @endforeach
        </table>

        <div class="deo" style="margin-top: 2.15mm">Део 9. Докази уз пријаву</div>
        <table class="mreza">
            <tr>
                <th class="rb" style="{{ $sb(12.1) }}{{ $v(10.2) }}">9.1 Редни број</th>
                <th colspan="2" style="{{ $s(260.7) }}">9.2 Назив и број доказа</th>
            </tr>
            @foreach (range(1, 4) as $r)
                <tr>
                    <td style="{{ $v(6.5) }}">@include('obrasci._kucice', ['vrednost' => null])</td>
                    <td style="{{ $s(196.7) }}">@include('obrasci._polje', ['vrednost' => null, 'sirina' => 188.6])</td>
                    <td style="{{ $s(63.8) }}"><span class="kvadrat"></span><img class="spajalica" src="data:image/svg+xml;base64,{{ base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path transform="rotate(-40 12 12)" d="M16 7v9.5a4 4 0 0 1-8 0V6a2.6 2.6 0 0 1 5.2 0v10a1.2 1.2 0 0 1-2.4 0V7.5" fill="none" stroke="#000" stroke-width="2.2" stroke-linecap="round"/></svg>') }}" alt=""></td>
                </tr>
            @endforeach
        </table>

        <div class="potpis">Потпис подносиоца пријаве у писменом облику</div>
    </div>

</body>
</html>
