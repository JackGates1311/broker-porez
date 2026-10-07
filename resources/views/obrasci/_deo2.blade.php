{{-- Deo 2: podaci o poreskom obavezniku. PIB (9 cifara) se upisuje u istih 13 kućica kao JMBG. --}}
@php($pib = str_pad((string) $obaveznik->pib, 13))
@php($tipovi = [
    1 => 'резидентно физичко лице',
    2 => 'предузетник који порез плаћа на паушално утврђен приход',
    3 => 'нерезидентно физичко лице које има боравиште у Републици',
    4 => 'нерезидентно физичко лице које нема боравиште у Републици',
])
@php($jmbgPodnosioca = $obaveznik->jmbg_podnosioca ?? str_repeat(' ', 13))
<div class="deo">Део 2. Подаци о пореском обвезнику</div>
<table class="polja">
    @if ($obrazac === 'ppdg')
        <tr>
            <th style="width: 30%">2.1 Тип пореског обвезника</th>
            <td>{{ isset($tipovi[$obaveznik->tip_obaveznika]) ? '('.$obaveznik->tip_obaveznika.') '.$tipovi[$obaveznik->tip_obaveznika] : '' }}</td>
            <th style="width: 22%">2.2 Порески идентификациони број (ЈМБГ/ЕБС/ПИБ)</th>
            <td>@include('obrasci._kucice', ['vrednost' => $pib])</td>
        </tr>
        <tr>
            <th>2.3 Име и презиме пореског обвезника</th>
            <td class="vrednost">{{ $obaveznik->ime_prezime }}</td>
            <th>2.4 Пребивалиште/седиште/општина</th>
            <td>{{ $obaveznik->prebivaliste }}</td>
        </tr>
        <tr>
            <th>2.5 Адреса пореског обвезника</th>
            <td>{{ $obaveznik->adresa }}</td>
            <th>2.6 Телефон</th>
            <td>{{ $obaveznik->telefon }}</td>
        </tr>
        <tr>
            <th>2.7 Електронска адреса</th>
            <td>{{ $obaveznik->email }}</td>
            <th>2.8 ЈМБГ подносиоца пријаве</th>
            <td>@include('obrasci._kucice', ['vrednost' => $jmbgPodnosioca])</td>
        </tr>
        <tr>
            <th>2.9 Земља резидентства</th>
            <td>{{ $obaveznik->zemlja_rezidentstva }}</td>
            <th>2.10 ЈМБГ/ПИБ пореског пуномоћника</th>
            <td>@if ($obaveznik->pib_punomocnika)@include('obrasci._kucice', ['vrednost' => $obaveznik->pib_punomocnika])@endif</td>
        </tr>
    @else
        <tr>
            <th style="width: 30%">2.1 Порески идентификациони број пореског обвезника (ЈМБГ/ЕБС/ПИБ)</th>
            <td>@include('obrasci._kucice', ['vrednost' => $pib])</td>
            <th style="width: 22%">2.5 ЈМБГ подносиоца пореске пријаве</th>
            <td>@include('obrasci._kucice', ['vrednost' => $jmbgPodnosioca])</td>
        </tr>
        <tr>
            <th>2.2 Име и презиме</th>
            <td class="vrednost">{{ $obaveznik->ime_prezime }}</td>
            <th>2.6 Телефон контакт особе</th>
            <td>{{ $obaveznik->telefon }}</td>
        </tr>
        <tr>
            <th>2.3 Адреса пореског обвезника</th>
            <td>{{ $obaveznik->adresa }}</td>
            <th>2.7 Електронска пошта</th>
            <td>{{ $obaveznik->email }}</td>
        </tr>
        <tr>
            <th>2.4 Пребивалиште</th>
            <td>{{ $obaveznik->prebivaliste }}</td>
            <th>2.8 Земља резидентства</th>
            <td>{{ $obaveznik->zemlja_rezidentstva }}</td>
        </tr>
    @endif
</table>
