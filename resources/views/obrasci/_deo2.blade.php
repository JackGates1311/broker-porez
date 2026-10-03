{{-- Deo 2: podaci o poreskom obvezniku, iz poreskog profila. --}}
<div class="deo">Део 2. Подаци о пореском обвезнику</div>
<table class="polja">
    @if ($obrazac === 'ppdg')
        <tr>
            <th style="width: 30%">2.1 Тип пореског обвезника</th>
            <td>Физичко лице</td>
            <th style="width: 22%">2.2 Порески идентификациони број (ЈМБГ/ЕБС/ПИБ)</th>
            <td>@include('obrasci._kucice', ['vrednost' => $profil->jmbg ?? str_repeat(' ', 13)])</td>
        </tr>
        <tr>
            <th>2.3 Име и презиме пореског обвезника</th>
            <td class="vrednost">{{ $profil->ime_prezime }}</td>
            <th>2.4 Пребивалиште/седиште/општина</th>
            <td>{{ $profil->prebivaliste_sifra }}</td>
        </tr>
        <tr>
            <th>2.5 Адреса пореског обвезника</th>
            <td>{{ $profil->adresa }}</td>
            <th>2.6 Телефон</th>
            <td>{{ $profil->telefon }}</td>
        </tr>
        <tr>
            <th>2.7 Електронска адреса</th>
            <td>{{ $profil->email }}</td>
            <th>2.8 ЈМБГ подносиоца пријаве</th>
            <td>@include('obrasci._kucice', ['vrednost' => $profil->jmbg ?? str_repeat(' ', 13)])</td>
        </tr>
        <tr>
            <th>2.9 Земља резидентства</th>
            <td>{{ $profil->zemlja_rezidentstva }}</td>
            <th>2.10 ЈМБГ/ПИБ пореског пуномоћника</th>
            <td></td>
        </tr>
    @else
        <tr>
            <th style="width: 30%">2.1 Порески идентификациони број пореског обвезника (ЈМБГ/ЕБС/ПИБ)</th>
            <td>@include('obrasci._kucice', ['vrednost' => $profil->jmbg ?? str_repeat(' ', 13)])</td>
            <th style="width: 22%">2.5 ЈМБГ подносиоца пореске пријаве</th>
            <td>@include('obrasci._kucice', ['vrednost' => $profil->jmbg ?? str_repeat(' ', 13)])</td>
        </tr>
        <tr>
            <th>2.2 Име и презиме</th>
            <td class="vrednost">{{ $profil->ime_prezime }}</td>
            <th>2.6 Телефон контакт особе</th>
            <td>{{ $profil->telefon }}</td>
        </tr>
        <tr>
            <th>2.3 Адреса пореског обвезника</th>
            <td>{{ $profil->adresa }}</td>
            <th>2.7 Електронска пошта</th>
            <td>{{ $profil->email }}</td>
        </tr>
        <tr>
            <th>2.4 Пребивалиште</th>
            <td>@include('obrasci._kucice', ['vrednost' => $profil->prebivaliste_sifra ?? '   '])</td>
            <th>2.8 Земља резидентства</th>
            <td>{{ $profil->zemlja_rezidentstva }}</td>
        </tr>
    @endif
</table>
