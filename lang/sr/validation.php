<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Poruke validacije (srpski, latinica)
    |--------------------------------------------------------------------------
    |
    | Pokrivena su pravila koja aplikacija koristi. Za ostala pravila Laravel
    | koristi rezervni jezik (APP_FALLBACK_LOCALE).
    |
    */

    'alpha' => 'Polje :attribute sme da sadrži samo slova.',
    'alpha_dash' => 'Polje :attribute sme da sadrži samo slova, brojeve, crtice i donje crte.',
    'array' => 'Polje :attribute mora biti lista.',
    'between' => [
        'numeric' => 'Polje :attribute mora biti između :min i :max.',
    ],
    'boolean' => 'Polje :attribute mora biti tačno ili netačno.',
    'confirmed' => 'Potvrda polja :attribute se ne poklapa.',
    'digits' => 'Polje :attribute mora imati tačno :digits cifara.',
    'digits_between' => 'Polje :attribute mora imati između :min i :max cifara.',
    'email' => 'Polje :attribute mora biti ispravna email adresa.',
    'file' => 'Polje :attribute mora biti fajl.',
    'in' => 'Izabrana vrednost polja :attribute nije ispravna.',
    'integer' => 'Polje :attribute mora biti ceo broj.',
    'lowercase' => 'Polje :attribute mora biti napisano malim slovima.',
    'max' => [
        'array' => 'Polje :attribute ne sme imati više od :max stavki.',
        'file' => 'Polje :attribute ne sme biti veće od :max kilobajta.',
        'numeric' => 'Polje :attribute ne sme biti veće od :max.',
        'string' => 'Polje :attribute ne sme imati više od :max karaktera.',
    ],
    'mimes' => 'Fajl u polju :attribute mora biti tipa: :values.',
    'min' => [
        'array' => 'Polje :attribute mora imati najmanje :min stavki.',
        'file' => 'Polje :attribute mora imati najmanje :min kilobajta.',
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'string' => 'Polje :attribute mora imati najmanje :min karaktera.',
    ],
    'password' => [
        'letters' => 'Polje :attribute mora sadržati najmanje jedno slovo.',
        'mixed' => 'Polje :attribute mora sadržati najmanje jedno veliko i jedno malo slovo.',
        'numbers' => 'Polje :attribute mora sadržati najmanje jedan broj.',
        'symbols' => 'Polje :attribute mora sadržati najmanje jedan simbol.',
        'uncompromised' => 'Uneta :attribute se pojavila u curenju podataka. Izaberite drugu.',
    ],
    'required' => 'Polje :attribute je obavezno.',
    'string' => 'Polje :attribute mora biti tekst.',
    'unique' => 'Uneto :attribute je već zauzeto.',

    'attributes' => [
        'adresa' => 'adresa',
        'email' => 'email',
        'fajl' => 'fajl',
        'fajlovi' => 'fajlovi',
        'fajlovi.*' => 'fajl',
        'godina' => 'godina',
        'ime_prezime' => 'ime i prezime',
        'jmbg' => 'JMBG',
        'kod' => 'kod',
        'korisnicko_ime' => 'korisničko ime',
        'lozinka' => 'lozinka',
        'nacin_ostvarivanja' => 'način ostvarivanja',
        'osnov_za_prijavu' => 'osnov za prijavu',
        'polugodiste' => 'polugodište',
        'prebivaliste_sifra' => 'šifra opštine',
        'sifra_vrste_prihoda' => 'šifra vrste prihoda',
        'telefon' => 'telefon',
        'token' => 'link',
        'vrsta_prijave' => 'vrsta prijave',
        'zemlja_rezidentstva' => 'zemlja rezidentstva',
    ],

];
