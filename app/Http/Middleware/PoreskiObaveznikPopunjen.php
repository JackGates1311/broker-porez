<?php

namespace App\Http\Middleware;

use App\Models\Korisnik;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PoreskiObaveznikPopunjen
{
    /**
     * Propušta samo korisnike koji su popunili podatke o poreskom obavezniku.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $korisnik = $request->user();

        if (! $korisnik instanceof Korisnik || ! $korisnik->poreskiObaveznik?->jePopunjen()) {
            abort_if($request->expectsJson(), 403, 'Podaci o poreskom obavezniku nisu popunjeni.');

            return redirect()->route('poreski-obaveznik');
        }

        return $next($request);
    }
}
