<?php

namespace App\Http\Controllers;

use App\Http\Requests\PodrskaRequest;
use App\Models\Korisnik;
use App\Services\PodrskaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PodrskaController extends Controller
{
    /**
     * Prima poruku iz widgeta (AJAX: JSON 201, greške 422; bez JS-a: povratak na stranicu).
     */
    public function posalji(PodrskaRequest $request, PodrskaService $servis): JsonResponse|RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        $servis->primi(
            $korisnik,
            $request->safe()->only(['naslov', 'poruka']),
            $request->file('prilozi', []),
            $request->input('stranica'),
            $request->userAgent(),
        );

        $poruka = 'Hvala! Poruka je primljena, odgovor stiže na email adresu vašeg naloga.';

        if ($request->expectsJson()) {
            return response()->json(['poruka' => $poruka], 201);
        }

        return back()->with('status', $poruka);
    }
}
