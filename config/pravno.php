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

    'verzija' => '2026-10-20',

    // Datum stupanja na snagu (Y-m-d).
    'vazi_od' => '2026-10-20',

    // Kratak opis izmena u odnosu na prethodnu verziju, za mejl obaveštenja.
    'izmene' => [],

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
            'svrha' => 'Slanje sistemskih mejlova: kod za potvrdu adrese i link za novu lozinku.',
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
