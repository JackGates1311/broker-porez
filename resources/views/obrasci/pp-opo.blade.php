@use('App\Support\Decimal')
@use('App\Support\Drzave')
{{--
    Obrazac PP OPO, raspored kao na zvaničnom obrascu (jedna strana); PDF sadrži samo obrazac.

    Zvanični obrazac je Excel tabela odštampana u razmeri 91,5 %: sav tekst je Calibri 7,32 pt, a
    kućice su tekst "|_|" od 10,07 pt, ovde nacrtane po izmerenoj meri (_kucice_opo). Carlito ima iste
    širine slova kao Calibri, pa se tekst poklapa. Mere su izmerene na obrascu, u mm; $s() i $v() pretvaraju meru ivica-do-ivice
    u širinu, odnosno visinu sadržaja ćelije (vidi ppdg-3r).
--}}
@php
    $o = $red->obracun;
    $font = 'sans';
    $margine = '15mm 7.6mm 5mm 6mm';
    $fs = 7.32;
    $fb = 10.07;
    // Mere kućica za _stil (ovde se ne crtaju, ali ih _stil očekuje).
    $k = 3.4;
    $kv = 2.7;
    $kr = 0.5;
    $fk = $fs;
    $fl = $fs;
    $flr = 0;
    $s = fn (float $mm) => 'width: '.($mm - 1.46).'mm;';
    $v = fn (float $mm) => 'height: '.($mm - 1.06).'mm;';
    // Širina ćelije sa većom levom marginom (uvučen tekst), da ukupna širina ostane ista.
    $sp = fn (float $mm, float $levo) => 'width: '.($mm - 0.86 - $levo).'mm; padding-left: '.$levo.'mm;';
    $iznos = fn ($x) => $x === null ? '' : Decimal::format($x);
    // 2.4: šifra opštine (config/opstine.php), 2.8: troslovna oznaka države (SRB).
    $sifraOpstine = $obaveznik->opstina_sifra;
    $prazno = 'border: 0; background: none; padding: 0;';
    $datum = fn ($d) => $d?->format('dmY');
@endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>ПП ОПО {{ $red->vreme->format('d.m.Y') }} {{ $red->simbol }}</title>
    @include('obrasci._stil')
    <style>
        body { font-size: {{ $fs }}pt; line-height: 1; }
        .naslov { width: 283.4mm; border-collapse: collapse; background: #f3f3f3; }
        .naslov td { border-top: 0.75pt solid #000; border-bottom: 0.75pt solid #000; height: 4.44mm; padding: 0.36mm 0 0 0.5mm; font-weight: bold; vertical-align: middle; }
        .naslov td.prva { border-left: 0.75pt solid #000; padding-left: 0.6mm; }
        .naslov td.poslednja { border-right: 0.75pt solid #000; text-align: right; }
        .deo { margin-top: 0.9mm; padding: 0 0 0 0.6mm; position: relative; top: 0.4mm; line-height: 1; }
        table.mreza > tbody > tr > th, table.mreza > tr > th,
        table.mreza > tbody > tr > td, table.mreza > tr > td { padding: 0.76mm 0.6mm 0; line-height: 1; }
        table.mreza > tbody > tr > .siva, table.mreza > tr > .siva { background: #eaeaea; }
        table.mreza > tbody > tr > .svetla, table.mreza > tr > .svetla { background: #f3f3f3; }
        table.mreza > tbody > tr > .tamna, table.mreza > tr > .tamna { background: #747474; font-weight: bold; }
        .natpis { white-space: nowrap; line-height: 1; }
        /* Kućice (_kucice_opo): crte i podvlake su apsolutno postavljene unutar .kd. */
        .kd { position: relative; height: 3.13mm; margin: 0 auto; }
        .levo > .kd { margin-left: 0; }
        .kd i { position: absolute; background: #000; }
        .kd b { position: absolute; bottom: -0.2mm; width: 3mm; text-align: center; font-weight: normal; font-size: {{ $fb }}pt; line-height: 1; }
        .legx { position: relative; height: 3.44mm; font-size: {{ $fs }}pt; line-height: 1; text-align: left; white-space: nowrap; }
        .legx span { position: absolute; top: 0; }
        .nize { position: relative; top: 0.42mm; }
        /* Ćelija fiksne visine iz koje tekst sme da izađe, kao u Excelu. */
        .preliv { overflow: visible; }
    </style>
</head>
<body>
    <table class="naslov">
        <tr>
            <td class="prva">ПОРЕСКА ПРИЈАВА О ОБРАЧУНАТОМ ПОРЕЗУ САМООПОРЕЗИВАЊЕМ И ПРИПАДАЈУЋИМ ДОПРИНОСИМА НА ЗАРАДУ/ДРУГУ ВРСТУ ПРИХОДА ОД СТРАНЕ ФИЗИЧКОГ ЛИЦА КАО ПОРЕСКОГ ОБВЕЗНИКА</td>
            <td class="poslednja">Образац ПП ОПО</td>
        </tr>
    </table>

    {{-- 1. Подаци о пореској пријави: леви блок 1.1–1.4а, десни 1.5–1.6а --}}
    <div class="deo">1. Подаци о пореској пријави</div>
    <table class="mreza">
        <tr>
            <th class="siva" style="{{ $s(15.6) }}{{ $v(10.5) }}">1.1 ВРСТА ПРИЈАВЕ</th>
            <th class="siva" style="{{ $s(32.3) }}">1.2 ОБРАЧУНСКИ ПЕРИОД</th>
            <th class="siva" style="{{ $s(35.1) }}">1.3 ДАТУМ ОСТВАРИВАЊА ПРИХОДА</th>
            <th class="siva" style="{{ $s(42.7) }}">1.4 ДАТУМ ДОСПЕЛОСТИ ПОРЕСКЕ ОБАВЕЗЕ</th>
            <th class="siva" style="{{ $s(38.3) }}">1.4а ДАТУМ ДО КОЈЕГ ЈЕ ОБРАЧУНАТА КАМАТА</th>
            <td style="width: 9.8mm; {{ $prazno }}"></td>
            <th class="siva" style="{{ $s(37.4 - 2.2) }} padding-right: 2.8mm">1.5 ИЗМЕНА ПРИЈАВЕ</th>
            <td style="{{ $s(11.7) }} padding-top: 3.4mm"><div class="nize" style="top: 0.35mm">@include('obrasci._kucice_opo', ['grupe' => [1], 'gore' => 0.72, 'desno' => 0.15])<div class="natpis">врста</div></div></td>
            <th class="siva levo" colspan="2" style="{{ $sp(60.2, 2.2) }}">1.6 ПО НАЛОГУ КОНТРОЛЕ/ОДЛУЦИ СУДА</th>
        </tr>
        <tr>
            <td style="{{ $v(9.5) }}">@include('obrasci._kucice_opo', ['vrednost' => $sifre['vrsta_prijave'], 'grupe' => [1], 'tip' => 'razmak', 'gore' => 0.81])<div class="natpis">1, 3, 4, 5</div></td>
            <td class="levo" style="{{ $sp(32.3, 1.9) }}">@include('obrasci._kucice_opo', ['vrednost' => $red->vreme->format('mY'), 'grupe' => [2, 4], 'spoj' => '   ', 'tip' => 'razmak', 'gore' => 0.81, 'legenda' => ['мм', 'гггг'], 'legendaX' => [1.93, 15.33]])</td>
            <td class="levo" style="{{ $sp(35.1, 2.87) }}">@include('obrasci._kucice_opo', ['vrednost' => $datum($red->vreme), 'grupe' => [2, 2, 4], 'tip' => 'razmak', 'gore' => 0.81, 'legenda' => ['дд', 'мм', 'гггг'], 'legendaX' => [1.0, 8.6, 19.7]])</td>
            <td class="levo" style="{{ $sp(42.7, 6.97) }}">@include('obrasci._kucice_opo', ['vrednost' => $datum($red->vreme->startOfDay()->addDays(30)), 'grupe' => [2, 2, 4], 'tip' => 'razmak', 'gore' => 0.81, 'legenda' => ['дд', 'мм', 'гггг'], 'legendaX' => [-1.3, 6.9, 18.0]])</td>
            <td class="levo" style="{{ $sp(38.3, 3.73) }}">@include('obrasci._kucice_opo', ['vrednost' => null, 'grupe' => [2, 2, 4], 'gore' => 0.81, 'legenda' => ['дд', 'мм', 'гггг'], 'legendaX' => [0.85, 8.45, 20.05]])</td>
            <td style="{{ $prazno }}"></td>
            <td colspan="2" class="levo" style="padding-left: 2.52mm; padding-top: 0">@include('obrasci._kucice_opo', ['duzina' => 37.21, 'krajPodvlake' => 0.34, 'podignuto' => 0.17, 'gore' => -0.07])<div class="natpis" style="text-align: center; padding-right: 6mm; margin-top: 0.95mm">1.5а Идентификациони број пријаве</div></td>
            <td style="{{ $s(43.8) }} padding-top: 0">@include('obrasci._kucice_opo', ['duzina' => 33.53, 'krajPodvlake' => 0.81, 'gore' => -0.07, 'desno' => -0.21])<div class="natpis" style="padding-right: 1.1mm; margin-top: 0.95mm">Број решења / одлуке суда</div></td>
            <td style="{{ $s(16.4) }}">@include('obrasci._kucice_opo', ['grupe' => [1], 'gore' => 0.85, 'desno' => 0.3])<div class="natpis">1.6а Основ</div></td>
        </tr>
    </table>

    {{-- 2. Подаци о пореском обвезнику --}}
    <div class="deo">2. Подаци о пореском обвезнику</div>
    <table class="mreza">
        <tr>
            <td class="siva levo" style="{{ $s(47.9) }}{{ $v(9.5) }}">2.1 ПОРЕСКИ ИДЕНТИФИКАЦИОНИ БРОЈ ПОРЕСКОГ ОБВЕЗНИКА (ЈМБГ/ЕБС/ПИБ)</td>
            <td class="levo" style="{{ $s(62.7) }}">{{ $obaveznik->pib }}</td>
            <td style="width: 63.2mm; {{ $prazno }}"></td>
            <td class="siva levo" style="{{ $s(49.1) }}">2.5 ЈМБГ ПОДНОСИОЦА ПОРЕСКЕ ПРИЈАВЕ</td>
            <td class="levo" style="{{ $sp(60.2, 3.73) }}">@include('obrasci._kucice_opo', ['vrednost' => $obaveznik->jmbg_podnosioca, 'grupe' => [13], 'gore' => 0.33])</td>
        </tr>
        <tr>
            <td class="siva levo" style="{{ $v(4.7) }}">2.2&nbsp; ИМЕ И ПРЕЗИМЕ</td>
            <td class="levo">{{ $obaveznik->ime_prezime }}</td>
            <td style="{{ $prazno }}"></td>
            <td class="siva levo">2.6 ТЕЛЕФОН КОНТАКТ ОСОБЕ</td>
            <td class="levo">{{ $obaveznik->telefon }}</td>
        </tr>
        <tr>
            <td class="siva levo" style="{{ $v(4.7) }}">2.3 АДРЕСА ПОРЕСКОГ ОБВЕЗНИКА</td>
            <td class="levo">{{ $obaveznik->adresa }}</td>
            <td style="{{ $prazno }}"></td>
            <td class="siva levo">2.7 ЕЛЕКТРОНСКА ПОШТА</td>
            <td class="levo">{{ $obaveznik->email }}</td>
        </tr>
        <tr>
            <td class="siva levo" style="{{ $v(4.6) }}">2.4 ПРЕБИВАЛИШТЕ</td>
            <td class="levo" style="padding-left: 1.19mm">@include('obrasci._kucice_opo', ['vrednost' => $sifraOpstine, 'grupe' => [3], 'gore' => 0.33])</td>
            <td colspan="3" style="{{ $prazno }}"></td>
        </tr>
    </table>
    <table class="mreza" style="margin-top: 1.5mm">
        <tr>
            <td class="siva levo" style="{{ $s(47.9) }}{{ $v(4.7) }}">2.8 ЗЕМЉА РЕЗИДЕНТСТВА</td>
            <td class="levo" style="{{ $sp(62.7, 1.19) }}">@include('obrasci._kucice_opo', ['vrednost' => Drzave::alfa3($obaveznik->zemlja_rezidentstva), 'grupe' => [3], 'gore' => 0.33])</td>
            <td class="siva levo" style="{{ $s(32) }}">2.9 БРОЈ ПАСОША</td>
            <td style="{{ $s(31.5) }}"></td>
            <td class="siva levo" style="{{ $sp(49.1, 1.8) }}">2.10 ПОРЕСКИ ПУНОМОЋНИК (ЈМБГ/ПИБ)</td>
            <td class="levo" style="{{ $sp(60.2, 3.73) }}">@include('obrasci._kucice_opo', ['vrednost' => $obaveznik->pib_punomocnika, 'grupe' => [13], 'gore' => 0.33])</td>
        </tr>
    </table>

    {{-- 3. Подаци о начину остваривања прихода --}}
    <div class="deo">3. Подаци о начину остваривања прихода</div>
    <table class="mreza">
        <tr>
            <td class="siva levo" style="{{ $s(47.9) }}{{ $v(6.7) }}">3.1 НАЧИН ОСТВАРИВАЊА ПРИХОДА</td>
            {{-- Na obrascu natpis "1 - 3" izlazi ispod ivice reda; visina sadržaja je zato zadata. --}}
            <td class="svetla" style="{{ $s(35.1) }} vertical-align: top; padding-top: 0.6mm"><div class="preliv" style="height: 5.6mm">@include('obrasci._kucice_opo', ['vrednost' => $sifre['nacin_ostvarivanja'], 'grupe' => [1], 'gore' => 0.59])<div class="natpis">1 - 3</div></div></td>
            <td class="siva" style="{{ $s(27.6) }}">3.2 ТЕКУЋИ РАЧУН</td>
            <td class="svetla levo" style="{{ $sp(93.3, 5.23) }}">@include('obrasci._kucice_opo', ['vrednost' => null, 'grupe' => [3, 13, 2], 'spoj' => '-', 'gore' => 0.33])</td>
            <td class="siva" style="{{ $s(19.3) }}">3.3 ОСТАЛО</td>
            <td class="svetla levo" style="{{ $s(60.2) }}">{{ $red->transakcija->izvor()->naziv() }}</td>
        </tr>
    </table>

    {{-- 4. Подаци о врстама прихода --}}
    <div class="deo">4. Подаци о врстама прихода</div>
    <table class="mreza">
        <tr>
            <th class="siva" style="{{ $s(7.7) }}{{ $v(9.3) }}">Р.Б.</th>
            <th class="siva" style="{{ $s(49.9) }}">ШИФРА ВРСТЕ ПРИХОДА</th>
            <th class="siva" style="{{ $s(12.7) }}">БРОЈ ДАНА</th>
            <th class="siva" style="width: {{ 12.7 - 0.26 }}mm; padding-left: 0; padding-right: 0">БРОЈ САТИ</th>
            <th class="siva" style="{{ $s(12.9) }}">ФОНД САТИ</th>
            <th class="siva" style="{{ $s(14.7) }}">БРУТО ПРИХОД</th>
            <th class="siva" style="{{ $s(15.1) }}">ОСНОВИЦА ЗА ПОРЕЗ</th>
            <th class="siva" style="{{ $s(16.9) }}">ОБРАЧУНАТИ ПОРЕЗ</th>
            <th class="siva" style="{{ $s(21.4) }}">ПОРЕЗ ПЛАЋЕН У ДРУГОЈ ДРЖАВИ</th>
            <th class="siva" style="{{ $s(19.9) }}">ПОРЕЗ ЗА УПЛАТУ</th>
            <th class="siva" style="{{ $s(20) }}">ОСНОВИЦА ЗА ДОПРИНОСЕ</th>
            <th class="siva" style="{{ $s(19.3) }}">ДОПРИНОС ЗА ПИО</th>
            <th class="siva" style="{{ $s(20.8) }}">ДОПРИНОС ЗА ЗДРАВСТВО</th>
            <th class="siva" style="{{ $s(23) }} vertical-align: top; padding-top: 0"><div class="preliv" style="height: 8.5mm; position: relative; top: -0.25mm">ДОПРИНОС ЗА СЛУЧАЈ НЕЗАПОСЛЕНОСТИ</div></th>
            <th class="siva" style="{{ $s(16.4) }}">МФП</th>
        </tr>
        <tr>
            @foreach (range(1, 15) as $i)
                <th class="siva" @if ($i === 1) style="{{ $v(4.6) }}" @endif>4.{{ $i }}</th>
            @endforeach
        </tr>
        @foreach (range(1, 6) as $rb)
            @php($prvi = $rb === 1)
            <tr>
                <td style="{{ $v(4.7) }}">{{ $prvi ? 1 : '' }}</td>
                <td>@include('obrasci._kucice_opo', ['vrednost' => $prvi ? $sifre['sifra_vrste_prihoda'] : null, 'grupe' => [1, 2, 3, 2, 1], 'spoj' => ' '])</td>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align: right">{{ $prvi ? $iznos($o->brutoRsd) : '' }}</td>
                <td style="text-align: right">{{ $prvi ? $iznos($o->brutoRsd) : '' }}</td>
                <td style="text-align: right">{{ $prvi ? $iznos($o->porez) : '' }}</td>
                <td style="text-align: right">{{ $prvi ? $iznos($o->placenPorezRsd) : '' }}</td>
                <td style="text-align: right">{{ $prvi ? $iznos($o->zaUplatu) : '' }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2" style="{{ $prazno }}{{ $v(4.8) }}"></td>
            <td colspan="3" class="tamna"><span class="nize">УКУПНО</span></td>
            <td class="tamna" style="text-align: right"><span class="nize">{{ $iznos($o->brutoRsd) }}</span></td>
            <td class="tamna" style="text-align: right"><span class="nize">{{ $iznos($o->brutoRsd) }}</span></td>
            <td class="tamna" style="text-align: right"><span class="nize">{{ $iznos($o->porez) }}</span></td>
            <td class="tamna" style="text-align: right"><span class="nize">{{ $iznos($o->placenPorezRsd) }}</span></td>
            <td class="tamna" style="text-align: right"><span class="nize">{{ $iznos($o->zaUplatu) }}</span></td>
            <td class="tamna"></td>
            <td class="tamna"></td>
            <td class="tamna"></td>
            <td class="tamna"></td>
            <td style="{{ $prazno }}"></td>
        </tr>
        <tr>
            <td colspan="8" style="{{ $prazno }}{{ $v(4.8) }}"></td>
            <td style="font-weight: bold"><span class="nize" style="top: 0.55mm">КАМАТА</span></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="{{ $prazno }}"></td>
        </tr>
    </table>

</body>
</html>
