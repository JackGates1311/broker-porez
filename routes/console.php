<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Na serveru treba cron: * * * * * cd /putanja/do/aplikacije && php artisan schedule:run
Schedule::command('zadovoljstvo:izvestaj --mejl')->monthlyOn(1, '08:00')->timezone('Europe/Belgrade');
