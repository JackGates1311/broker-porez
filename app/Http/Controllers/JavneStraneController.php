<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JavneStraneController extends Controller
{
    /**
     * Gost vidi predstavljanje aplikacije, a prijavljen korisnik ide u svoju knjigu.
     */
    public function pocetna(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('pocetna');
        }

        return view('javno.pocetna');
    }

    public function politika(): View
    {
        return view('pravno.politika');
    }

    public function uslovi(): View
    {
        return view('pravno.uslovi');
    }
}
