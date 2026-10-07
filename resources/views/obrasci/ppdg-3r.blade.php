@use('App\Support\Decimal')
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>ППДГ-3Р {{ $period->kod }}</title>
    @include('obrasci._stil')
</head>
<body>
    <table class="zaglavlje">
        <tr>
            <td><h1>Пореска пријава за утврђивање пореза на капиталне добитке</h1></td>
            <td class="oznaka-obrasca">Образац ППДГ-3Р</td>
        </tr>
    </table>

    <div class="deo">Део 1. Подаци о пријави</div>
    <table class="polja">
        <tr>
            <th style="width: 7%">1.1 Врста пријаве</th>
            <th style="width: 7%">1.1а Основ за пријаву</th>
            <th>1.2 Датум остваривања прихода/дела прихода</th>
            <th>1.3 Датум доспелости за подношење пореске пријаве</th>
            <th>1.4 Датум и начин подношења пријаве</th>
            <th style="width: 9%">1.5 Измена/Сторнирање пријаве</th>
            <th style="width: 16%">1.5а Идентификациони број пријаве</th>
        </tr>
        <tr>
            <td class="centar">@include('obrasci._kucice', ['vrednost' => $sifre['vrsta_prijave']])</td>
            <td class="centar">@include('obrasci._kucice', ['vrednost' => $sifre['osnov_za_prijavu']])</td>
            <td>@include('obrasci._datum', ['datum' => $period->do])</td>
            <td>@include('obrasci._datum', ['datum' => $period->datumDospelosti()])</td>
            <td>@include('obrasci._datum', ['datum' => now('Europe/Belgrade')])</td>
            <td class="centar">@include('obrasci._kucice', ['vrednost' => ' '])</td>
            <td></td>
        </tr>
    </table>

    @include('obrasci._deo2', ['obaveznik' => $obaveznik, 'obrazac' => 'ppdg'])

    <div class="deo">Део 4. Подаци за утврђивање пореза код преноса хартија од вредности/инвестиционих јединица ({{ $period->od->format('d.m.Y') }}–{{ $period->do->format('d.m.Y') }})</div>
    <table class="polja">
        <thead>
            <tr>
                <th style="width: 4%">4.1 Редни број</th>
                <th style="width: 14%">4.2 Назив емитента/ инвестиционог фонда</th>
                <th style="width: 8%">4.3 Датум преноса</th>
                <th style="width: 10%">4.4 Број документа о преносу</th>
                <th style="width: 8%">4.5 Број пренетих ХоВ/ инвестиционих јединица</th>
                <th style="width: 10%">4.6 Продајна цена (RSD)</th>
                <th style="width: 8%">4.7 Датум стицања</th>
                <th style="width: 10%">4.8 Број документа о стицању</th>
                <th style="width: 8%">4.9 Број стечених ХоВ/ инвестиционих јединица</th>
                <th style="width: 10%">4.10 Набавна цена (RSD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prodaje as $i => $prodaja)
                @php($stavke = $prodaja->alokacije->isEmpty() ? collect([null]) : $prodaja->alokacije)
                @foreach ($stavke as $j => $alokacija)
                    <tr @class(['podred' => $j > 0])>
                        @if ($j === 0)
                            <td rowspan="{{ $stavke->count() }}" class="centar vrednost">{{ $i + 1 }}</td>
                            <td rowspan="{{ $stavke->count() }}">{{ $prodaja->naziv ?? $prodaja->simbol }}<br><span class="pomoc">{{ $prodaja->transakcija->imovina?->isin }}</span></td>
                            <td rowspan="{{ $stavke->count() }}" class="centar">{{ $prodaja->vreme->format('d.m.Y') }}</td>
                            <td rowspan="{{ $stavke->count() }}">{{ $prodaja->transakcija->broker_transakcija_id }}</td>
                            <td rowspan="{{ $stavke->count() }}" class="broj">{{ Decimal::formatKolicina($prodaja->kolicina) }}</td>
                            <td rowspan="{{ $stavke->count() }}" class="broj vrednost">{{ Decimal::format($prodaja->vrednost_rsd) }}</td>
                        @endif
                        @if ($alokacija)
                            <td class="centar">{{ $alokacija->vreme->format('d.m.Y') }}</td>
                            <td>{{ $alokacija->broker_id }}</td>
                            <td class="broj">{{ Decimal::formatKolicina($alokacija->kolicina) }}</td>
                            <td class="broj">{{ Decimal::format($alokacija->nabavna_rsd) }}</td>
                        @else
                            <td colspan="4" class="pomoc">Нема података о стицању у увезеним изводима.</td>
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="10" class="centar">У изабраном полугодишту нема преноса хартија од вредности.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="deo">Обрачун (информативно, по FIFO методу)</div>
    <table class="polja" style="width: 70%">
        <tr>
            <th>Укупна продајна цена</th>
            <th>Укупна набавна цена</th>
            <th>Капитални добитак / губитак</th>
            <th>Порез 15%</th>
            <th>Губитак за пребијање</th>
        </tr>
        <tr>
            <td class="broj vrednost">{{ Decimal::format($zbir['prodajna_rsd']) }}</td>
            <td class="broj vrednost">{{ Decimal::format($zbir['nabavna_rsd']) }}</td>
            <td class="broj vrednost">{{ Decimal::format($zbir['neto_dobit']) }}</td>
            <td class="broj vrednost">{{ Decimal::format($zbir['trenutni_dug']) }}</td>
            <td class="broj vrednost">{{ Decimal::format($zbir['preostalo_za_prebijanje']) }}</td>
        </tr>
    </table>

    <p class="napomena">
        Делови 3, 5, 6, 7, 8 и 9 нису попуњени. Износи су у динарима, по средњем курсу НБС на дан преноса, односно стицања.
        Шифре у Делу 1 проверите пре подношења пријаве.
    </p>

    <table class="potpis">
        <tr>
            <td></td>
            <td><div class="linija-potpisa">Потпис подносиоца пријаве у писменом облику</div></td>
        </tr>
    </table>
</body>
</html>
