<?php

use App\Http\Middleware\EmailVerifikovan;
use App\Http\Middleware\PoreskiObaveznikPopunjen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verifikovan' => EmailVerifikovan::class,
            'obaveznik' => PoreskiObaveznikPopunjen::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('prijava'));
        $middleware->redirectUsersTo(fn () => route('pocetna'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
