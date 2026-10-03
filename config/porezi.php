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
    | Šifre na obrascima — PROVERITI PRE PODNOŠENJA
    |--------------------------------------------------------------------------
    |
    | Vrednosti ispod su podrazumevane i nisu potvrđene u zvaničnom šifarniku.
    | Pre podnošenja prijave proverite ih u uputstvu Poreske uprave i po potrebi
    | promenite ovde ili u formi za preuzimanje obrasca.
    |
    */

    'ppdg3r' => [
        'vrsta_prijave' => '1',
        'osnov_za_prijavu' => '1',
    ],

    'ppopo' => [
        'vrsta_prijave' => '1',
        'sifra_vrste_prihoda' => '111402000',
        'nacin_ostvarivanja' => '3',
    ],

];
