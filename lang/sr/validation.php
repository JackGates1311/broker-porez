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

    'accepted' => 'Morate prihvatiti :attribute.',
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
    'different' => 'Polja :attribute i :other moraju se razlikovati.',
    'email' => 'Polje :attribute mora biti ispravna email adresa.',
    'enum' => 'Izabrana vrednost polja :attribute nije ispravna.',
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
    'present' => 'Polje :attribute mora biti poslato.',
    'password' => [
        'letters' => 'Polje :attribute mora sadržati najmanje jedno slovo.',
        'mixed' => 'Polje :attribute mora sadržati najmanje jedno veliko i jedno malo slovo.',
        'numbers' => 'Polje :attribute mora sadržati najmanje jedan broj.',
        'symbols' => 'Polje :attribute mora sadržati najmanje jedan simbol.',
        'uncompromised' => 'Uneta :attribute se pojavila u curenju podataka. Izaberite drugu.',
    ],
    'regex' => 'Format polja :attribute nije ispravan.',
    'required' => 'Polje :attribute je obavezno.',
    'required_if' => 'Polje :attribute je obavezno kada je :other :value.',
    'string' => 'Polje :attribute mora biti tekst.',
    'unique' => 'Uneto :attribute je već zauzeto.',

    'values' => [
        'tip_obaveznika' => [
            '3' => 'nerezident sa boravištem u Republici',
            '4' => 'nerezident bez boravišta u Republici',
        ],
    ],

    'attributes' => [
        'adresa' => 'adresa',
        'broj_pasosa' => 'broj pasoša',
        'akcije' => 'akcije',
        'akcije.*.tip' => 'tip transakcije',
        'akcije.*.vrednost' => 'vrednost akcije',
        'decimalni_separator' => 'decimalni separator',
        'email' => 'email',
        'fajl' => 'fajl',
        'fajl_rucni' => 'CSV fajl',
        'fajlovi' => 'fajlovi',
        'fajlovi.*' => 'fajl',
        'format_datuma' => 'format datuma',
        'godina' => 'godina',
        'ime_prezime' => 'ime i prezime',
        'jmbg' => 'JMBG',
        'jmbg_podnosioca' => 'JMBG podnosioca prijave',
        'kod' => 'kod',
        'kodna_strana' => 'kodna strana',
        'kolone' => 'kolone',
        'korisnicko_ime' => 'korisničko ime',
        'lozinka' => 'lozinka',
        'nacin_ostvarivanja' => 'način ostvarivanja',
        'naziv_sablona' => 'naziv šablona',
        'osnov_za_prijavu' => 'osnov za prijavu',
        'pib' => 'poreski identifikacioni broj',
        'pib_punomocnika' => 'JMBG/PIB punomoćnika',
        'polugodiste' => 'polugodište',
        'potvrda_emaila' => 'potvrda email adrese',
        'prihvatam_uslove' => 'Uslove korišćenja i Politiku privatnosti',
        'prebivaliste' => 'prebivalište/boravište/sedište/opština ostvarivanja prihoda',
        'red_zaglavlja' => 'red zaglavlja',
        'opstina_sifra' => 'opština prebivališta',
        'sablon_id' => 'šablon',
        'separator' => 'separator kolona',
        'separator_hiljada' => 'separator hiljada',
        'sifra_vrste_prihoda' => 'šifra vrste prihoda',
        'telefon' => 'telefon',
        'tekuci_racun' => 'tekući račun',
        'tip_obaveznika' => 'tip obaveznika',
        'token' => 'link',
        'vremenska_zona' => 'vremenska zona',
        'vrsta_prijave' => 'vrsta prijave',
        'zemlja_rezidentstva' => 'zemlja rezidentstva',
    ],

    'custom' => [
        'naziv_sablona' => [
            'required_if' => 'Upišite naziv šablona da biste ga sačuvali.',
        ],
        'email' => [
            'regex' => 'Email adresa mora imati domen sa nastavkom, npr. ime@primer.rs.',
        ],
        'telefon' => [
            'regex' => 'Broj telefona sme da sadrži samo cifre, razmake i znakove + - / ( ) i mora imati od 6 do 15 cifara.',
        ],
        'tekuci_racun' => [
            'digits' => 'Tekući račun upišite kao 265-0000000360051-46, 265-360051-46 ili 18 cifara.',
            'kontrolni_broj' => 'Kontrolni broj tekućeg računa (poslednje dve cifre) nije ispravan. Proverite broj.',
        ],
        'broj_pasosa' => [
            'regex' => 'Broj pasoša sme da sadrži samo slova i cifre.',
        ],
        'prihvatam_uslove' => [
            'accepted' => 'Da biste nastavili, prihvatite Uslove korišćenja i Politiku privatnosti.',
        ],
        'potvrda_emaila' => [
            'in' => 'Upisana adresa se ne poklapa sa email adresom naloga.',
        ],
    ],

];
