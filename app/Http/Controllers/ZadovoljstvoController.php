<?php

namespace App\Http\Controllers;

use App\Http\Requests\ZadovoljstvoRequest;
use App\Models\Korisnik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ZadovoljstvoController extends Controller
{
    /**
     * Čuva ocenu ili odlaže pitanje; u oba slučaja pomera zadovoljstvo_od (kada se sledeći put pita).
     */
    public function sacuvaj(ZadovoljstvoRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $akcija = $request->validated('akcija');

        if ($akcija === 'posalji') {
            $korisnik->oceneZadovoljstva()->create([
                'ocena' => (int) $request->validated('ocena'),
                'komentar' => filled($request->validated('komentar')) ? $request->validated('komentar') : null,
                'povod' => $request->validated('povod'),
            ]);
        }

        $korisnik->forceFill(['zadovoljstvo_od' => match ($akcija) {
            'posalji' => now()->addDays((int) config('podrska.zadovoljstvo.pauza_posle_odgovora_dana')),
            'kasnije' => now()->addDays((int) config('podrska.zadovoljstvo.pauza_ne_sada_dana')),
            'nikad' => null,
        }])->save();

        $poruka = $akcija === 'posalji' ? 'Hvala na oceni!' : null;

        if ($request->expectsJson()) {
            return response()->json(['poruka' => $poruka], $akcija === 'posalji' ? 201 : 200);
        }

        return $poruka !== null ? back()->with('status', $poruka) : back();
    }
}
