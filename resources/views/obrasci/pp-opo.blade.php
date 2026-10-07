@use('App\Support\Decimal')
@php($o = $red->obracun)
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>ПП ОПО {{ $red->vreme->format('d.m.Y') }} {{ $red->simbol }}</title>
    @include('obrasci._stil')
</head>
<body>
    <table class="zaglavlje">
        <tr>
            <td><h1>Пореска пријава о обрачунатом порезу самоопорезивањем и припадајућим доприносима на зараду/другу врсту прихода од стране физичког лица као пореског обвезника</h1></td>
            <td class="oznaka-obrasca">Образац ПП ОПО</td>
        </tr>
    </table>

    <div class="deo">1. Подаци о пореској пријави</div>
    <table class="polja">
        <tr>
            <th style="width: 8%">1.1 Врста пријаве</th>
            <th>1.2 Обрачунски период</th>
            <th>1.3 Датум остваривања прихода</th>
            <th>1.4 Датум доспелости пореске обавезе</th>
            <th>1.4а Датум до којег је обрачуната камата</th>
            <th style="width: 8%">1.5 Измена пријаве</th>
            <th style="width: 14%">1.5а Идентификациони број пријаве</th>
        </tr>
        <tr>
            <td class="centar">@include('obrasci._kucice', ['vrednost' => $sifre['vrsta_prijave']])</td>
            <td>
                @include('obrasci._kucice', ['vrednost' => $red->vreme->format('m.Y')])
                <div class="pomoc">мм &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; гггг</div>
            </td>
            <td>@include('obrasci._datum', ['datum' => $red->vreme])</td>
            <td>@include('obrasci._datum', ['datum' => $red->vreme->startOfDay()->addDays(30)])</td>
            <td>@include('obrasci._datum', ['datum' => null])</td>
            <td class="centar">@include('obrasci._kucice', ['vrednost' => ' '])</td>
            <td></td>
        </tr>
    </table>

    @include('obrasci._deo2', ['obaveznik' => $obaveznik, 'obrazac' => 'ppopo'])

    <div class="deo">3. Подаци о начину остваривања прихода</div>
    <table class="polja" style="width: 60%">
        <tr>
            <th style="width: 30%">3.1 Начин остваривања прихода</th>
            <td class="centar">@include('obrasci._kucice', ['vrednost' => $sifre['nacin_ostvarivanja']])</td>
            <th style="width: 20%">3.3 Остало</th>
            <td>Trading 212</td>
        </tr>
    </table>

    <div class="deo">4. Подаци о врстама прихода</div>
    <table class="polja">
        <thead>
            <tr>
                <th>4.1 Р.б.</th>
                <th style="width: 14%">4.2 Шифра врсте прихода</th>
                <th>4.3 Број дана</th>
                <th>4.4 Број сати</th>
                <th>4.5 Фонд сати</th>
                <th>4.6 Бруто приход</th>
                <th>4.7 Основица за порез</th>
                <th>4.8 Обрачунати порез</th>
                <th>4.9 Порез плаћен у другој држави</th>
                <th>4.10 Порез за уплату</th>
                <th>4.11 Основица за доприносе</th>
                <th>4.12–4.15 Доприноси</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="centar vrednost">1</td>
                <td>@include('obrasci._kucice', ['vrednost' => $sifre['sifra_vrste_prihoda']])</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="broj vrednost">{{ Decimal::format($o->brutoRsd) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->brutoRsd) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->porez) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->placenPorezRsd) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->zaUplatu) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5" class="broj">УКУПНО</td>
                <td class="broj vrednost">{{ Decimal::format($o->brutoRsd) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->brutoRsd) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->porez) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->placenPorezRsd) }}</td>
                <td class="broj vrednost">{{ Decimal::format($o->zaUplatu) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="deo">Обрачун дивиденде (информативно)</div>
    <table class="polja">
        <tr>
            <th>Емитент</th>
            <th>ISIN</th>
            <th>Број акција</th>
            <th>Дивиденда по акцији (нето)</th>
            <th>Порез по одбитку</th>
            <th>Бруто ({{ $red->valuta }})</th>
            <th>Средњи курс НБС</th>
            <th>Порез у иностранству</th>
        </tr>
        <tr>
            <td>{{ $red->naziv ?? $red->simbol }} ({{ $red->simbol }})</td>
            <td>{{ $red->isin }}</td>
            <td class="broj">{{ Decimal::formatKolicina($red->kolicina) }}</td>
            <td class="broj">{{ Decimal::format($red->neto_po_akciji, 6) }} {{ $red->valuta }}</td>
            <td class="broj">{{ Decimal::format($red->porez_po_odbitku) }} {{ $red->transakcija->valuta_poreza }}</td>
            <td class="broj">{{ Decimal::format($o->bruto, 4) }}</td>
            <td class="broj">{{ Decimal::format($red->kurs, 4) }}</td>
            <td class="broj">{{ Decimal::format($o->procenatPoreza * 100) }}%</td>
        </tr>
    </table>

    <p class="napomena">
        Порез за уплату = 15% бруто прихода умањено за порез плаћен у другој држави (не мање од нуле).
        Шифре у деловима 1, 3 и 4.2 проверите пре подношења пријаве.
    </p>

    <table class="potpis">
        <tr>
            <td></td>
            <td><div class="linija-potpisa">Потпис подносиоца пријаве</div></td>
        </tr>
    </table>
</body>
</html>
