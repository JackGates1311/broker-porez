<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Poruke podrške idu preko istog Brevo naloga kao kodovi za potvrdu, pa ne smeju da potroše dnevnu kvotu.
        RateLimiter::for('podrska', fn (Request $request) => [
            Limit::perMinutes(10, 5)->by('podrska-10min:'.$request->user()?->getAuthIdentifier()),
            Limit::perDay(10)->by('podrska-dan:'.$request->user()?->getAuthIdentifier()),
        ]);
    }
}
