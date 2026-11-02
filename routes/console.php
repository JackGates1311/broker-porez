<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Na serveru treba cron: * * * * * cd /putanja/do/aplikacije && php artisan schedule:run
Schedule::command('zadovoljstvo:izvestaj --mejl')->monthlyOn(1, '08:00')->timezone('Europe/Belgrade');

// Obaveštenje o novoj verziji uslova (docs/nova_verzija_uslova.txt). Kad su svi obavešteni, ne šalje ništa.
// Limit ostavlja deo Brevo dnevne kvote za kodove za verifikaciju; neuspeh (i prekratak rok) stiže mejlom.
Schedule::command('pravno:obavesti --limit=200')->dailyAt('09:00')->timezone('Europe/Belgrade')
    ->withoutOverlapping()->emailOutputOnFailure(config('podrska.email'));
