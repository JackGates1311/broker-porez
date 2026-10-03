<?php

namespace App\Http\Middleware;

use App\Models\Korisnik;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailVerifikovan
{
    /**
     * Propušta samo korisnike koji su potvrdili email adresu.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $korisnik = $request->user();

        if (! $korisnik instanceof Korisnik || ! $korisnik->jeVerifikovan()) {
            abort_if($request->expectsJson(), 403, 'Email adresa nije potvrđena.');

            return redirect()->guest(route('verifikacija'));
        }

        return $next($request);
    }
}
