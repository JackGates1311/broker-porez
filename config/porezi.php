<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Srednji kurs NBS
    |--------------------------------------------------------------------------
    |
    | Javni API sa podacima NBS: GET {kurs_api_url}/rates/{Y-m-d} vraća sve
    | valute za dan, sa poljima exchange_middle i parity.
    |
    */

    'kurs_api_url' => env('KURS_API_URL', 'https://kurs.resenje.org/api/v1'),

    'kurs_api_timeout' => 10,

    /*
    |--------------------------------------------------------------------------
    | Poreska stopa (kapitalna dobit i dividende)
    |--------------------------------------------------------------------------
    */

    'stopa' => '0.15',

    /*
    |--------------------------------------------------------------------------
    | Šifre na obrascima
    |--------------------------------------------------------------------------
    |
    | Ponuđene oznake (vrsta prijave, osnov za prijavu, način ostvarivanja)
    | su iz uputstava Poreske uprave za PPDG-3R i PP OPO; korisnik ih bira u
    | padajućem meniju. Šifra vrste prihoda (PP OPO 4.2) nije potvrđena u
    | šifarniku — ponuđena je kao predlog, a može se upisati i druga.
    |
    */

    'ppdg3r' => [
        'vrsta_prijave' => '1',
        'osnov_za_prijavu' => '4',
        'vrste_prijave' => [
            '1' => 'Opšta prijava (u roku)',
            '3' => 'Prijava po čl. 182b ZPPPA (posle roka)',
            '6' => 'Prijava po čl. 39. ZPPPA (produžen rok)',
        ],
        'osnovi_za_prijavu' => [
            '1' => 'Prenos stvarnih prava na nepokretnosti',
            '2' => 'Prenos autorskih i srodnih prava i prava industrijske svojine',
            '3' => 'Prenos udela u kapitalu pravnih lica',
            '4' => 'Prenos akcija i ostalih hartija od vrednosti, uključujući investicione jedinice',
            '5' => 'Prodaja nepokretnosti uz pravo na poresko oslobođenje',
        ],
    ],

    'ppopo' => [
        'vrsta_prijave' => '1',
        'sifra_vrste_prihoda' => '111402000',
        'nacin_ostvarivanja' => '1',
        'vrste_prijave' => [
            '1' => 'Opšta prijava',
            '3' => 'Prijava po čl. 182b ZPPPA (posle roka)',
            '4' => 'Prijava po nalogu kontrole',
            '5' => 'Prijava po odluci suda',
        ],
        'sifre_vrste_prihoda' => [
            '111402000' => 'Dividende',
        ],
        'nacini_ostvarivanja' => [
            '1' => 'Isplata na račun',
            '2' => 'Isplata gotovine',
            '3' => 'U drugom obliku',
        ],
    ],

];
