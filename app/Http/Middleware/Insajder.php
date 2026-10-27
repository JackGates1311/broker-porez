<?php

namespace App\Http\Middleware;

use App\Models\Korisnik;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Insajder
{
    /**
     * Propušta samo insajdere (korisnici.is_insajder, uključuje se u Moj nalog). Ostalima je ruta nepostojeća (404).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $korisnik = $request->user();

        abort_unless($korisnik instanceof Korisnik && $korisnik->jeInsajder(), 404);

        return $next($request);
    }
}
