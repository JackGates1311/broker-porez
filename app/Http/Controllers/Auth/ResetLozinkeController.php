<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetLozinkeRequest;
use App\Services\ResetLozinkeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResetLozinkeController extends Controller
{
    public function create(string $token, ResetLozinkeService $reset): View
    {
        return view('auth.reset-lozinke', [
            'token' => $token,
            'linkVazi' => $reset->nadjiKorisnika($token) !== null,
        ]);
    }

    public function store(ResetLozinkeRequest $request, ResetLozinkeService $reset): RedirectResponse
    {
        if (! $reset->resetuj($request->validated('token'), $request->validated('lozinka'))) {
            throw ValidationException::withMessages([
                'token' => 'Link za resetovanje nije ispravan ili je istekao. Zatražite novi.',
            ]);
        }

        return redirect()->route('prijava')
            ->with('status', 'Lozinka je promenjena. Prijavite se novom lozinkom.');
    }
}
