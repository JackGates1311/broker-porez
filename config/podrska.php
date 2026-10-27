<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Podrška (widget za povratne informacije insajdera)
    |--------------------------------------------------------------------------
    |
    | Poruke idu na ovu adresu preko podrazumevanog mailera (Brevo SMTP), sa
    | From = MAIL_FROM_ADDRESS i Reply-To = email korisnika, pa odgovor iz
    | sandučeta stiže direktno korisniku.
    |
    */

    'email' => env('PODRSKA_EMAIL') ?: 'podrska@brokerporez.net',

    // Pitanje o zadovoljstvu posle izvoza obrasca (samo insajderi): koliko dana se ne pita ponovo.
    'zadovoljstvo' => [
        'pauza_posle_odgovora_dana' => 90,
        'pauza_ne_sada_dana' => 7,
    ],

];
