<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Uslovi korišćenja i Politika privatnosti
    |--------------------------------------------------------------------------
    |
    | Verzija se pamti uz prihvatanje (korisnici.uslovi_verzija). Kada se tekst
    | suštinski promeni (ne zbog slovnih grešaka):
    |   1. povećajte 'verzija', postavite 'vazi_od' najmanje 15 dana unapred
    |      i u 'izmene' ukratko navedite šta se menja;
    |   2. posle deploy-a pokrenite php artisan pravno:obavesti (mejl svima).
    | Do 'vazi_od' važi i ranije prihvaćena verzija; posle toga korisnik pre
    | rada u knjizi ponovo prihvata uslove na koraku Poreski obaveznik.
    |
    */

    'verzija' => '2026-11-15',

    // Datum stupanja na snagu (Y-m-d).
    'vazi_od' => '2026-11-15',

    // Kratak opis izmena u odnosu na prethodnu verziju, za mejl obaveštenja.
    'izmene' => [
        'Uvedene su povratne informacije koje uključujete sami u Moj nalog: poruke podrške sa prilozima i povremeno pitanje o zadovoljstvu (ocena od 1 do 5) posle izvoza obrasca. Čuvaju se dok imate nalog i brišu se zajedno sa njim.',
        'Novi obrađivač: Zoho Corporation B.V. (Holandija), sanduče podrške u koje stižu vaše poruke i iz kog vam odgovaramo.',
        'Brevo, pored sistemskih mejlova, sada prosleđuje i vaše poruke podrške u sanduče podrške.',
        'Email adresu koristimo i za odgovor na poruke koje nam pošaljete.',
    ],

    // Rukovalac podacima o ličnosti (ZZPL čl. 23, GDPR čl. 13).
    'rukovalac' => [
        'ime' => env('PRAVNO_RUKOVALAC_IME') ?: '[Ime i prezime]',
        'mesto' => env('PRAVNO_RUKOVALAC_MESTO') ?: '[Mesto], Republika Srbija',
        'email' => env('PRAVNO_EMAIL') ?: 'privatnost@brokerporez.net',
    ],

    'lokacija_servera' => env('PRAVNO_LOKACIJA_SERVERA') ?: 'Nemačka (Evropska unija)',

    // Obrađivači kojima se poveravaju podaci; svi su u EU.
    'obradjivaci' => [
        [
            'naziv' => 'Hetzner Online GmbH',
            'sediste' => 'Nemačka',
            'svrha' => 'Server i baza podataka na kojima radi aplikacija.',
        ],
        [
            'naziv' => 'Sendinblue SAS (Brevo)',
            'sediste' => 'Francuska',
            'svrha' => 'Slanje sistemskih mejlova (kod za potvrdu adrese, link za novu lozinku) i prosleđivanje poruka podrške u sanduče podrške.',
        ],
        [
            'naziv' => 'Zoho Corporation B.V. (Zoho Mail)',
            'sediste' => 'Holandija',
            'svrha' => 'Sanduče podrške ('.(env('PODRSKA_EMAIL') ?: 'podrska@brokerporez.net').'): prijem poruka podrške i odgovori na njih.',
        ],
        [
            'naziv' => 'Google Ireland Limited',
            'sediste' => 'Irska',
            'svrha' => 'Samo ako izaberete prijavu Google nalogom: potvrda identiteta.',
        ],
    ],

    // Koliko dugo se čuvaju logovi i rezervne kopije (navodi se u politici).
    'rok_logova_dana' => (int) env('LOG_DAILY_DAYS', 14),
    'rok_rezervnih_kopija_dana' => (int) env('PRAVNO_ROK_REZERVNIH_KOPIJA', 30),

];
