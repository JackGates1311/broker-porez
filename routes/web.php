<?php

use App\Http\Controllers\Auth\PrijavaController;
use App\Http\Controllers\Auth\RegistracijaController;
use App\Http\Controllers\Auth\ResetLozinkeController;
use App\Http\Controllers\Auth\VerifikacijaController;
use App\Http\Controllers\Auth\ZaboravljenaLozinkaController;
use App\Http\Controllers\Porezi\DashboardController;
use App\Http\Controllers\Porezi\DividendeController;
use App\Http\Controllers\Porezi\IzvozController;
use App\Http\Controllers\Porezi\KapitalnaDobitController;
use App\Http\Controllers\Porezi\PoreskiProfilController;
use App\Http\Controllers\Porezi\UvozController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pocetna');

Route::middleware('guest')->group(function () {
    Route::get('/registracija', [RegistracijaController::class, 'create'])->name('registracija');
    Route::post('/registracija', [RegistracijaController::class, 'store']);

    Route::get('/prijava', [PrijavaController::class, 'create'])->name('prijava');
    Route::post('/prijava', [PrijavaController::class, 'store']);

    Route::get('/zaboravljena-lozinka', [ZaboravljenaLozinkaController::class, 'create'])->name('lozinka.zaboravljena');
    Route::post('/zaboravljena-lozinka', [ZaboravljenaLozinkaController::class, 'store']);
    Route::get('/zaboravljena-lozinka/poslato', [ZaboravljenaLozinkaController::class, 'poslato'])->name('lozinka.poslato');

    Route::get('/resetovanje-lozinke/{token}', [ResetLozinkeController::class, 'create'])->name('lozinka.reset');
    Route::post('/resetovanje-lozinke', [ResetLozinkeController::class, 'store'])->name('lozinka.reset.sacuvaj');
});

Route::middleware('auth')->group(function () {
    Route::get('/verifikacija', [VerifikacijaController::class, 'create'])->name('verifikacija');
    Route::post('/verifikacija', [VerifikacijaController::class, 'store']);
    Route::post('/verifikacija/ponovo', [VerifikacijaController::class, 'ponovoPosalji'])->name('verifikacija.ponovo');

    Route::post('/odjava', [PrijavaController::class, 'destroy'])->name('odjava');

    Route::middleware('verifikovan')->group(function () {
        Route::get('/pocetna', DashboardController::class)->name('pocetna');
        Route::get('/kapitalna-dobit', KapitalnaDobitController::class)->name('kapitalna-dobit');
        Route::get('/dividende', DividendeController::class)->name('dividende');

        Route::get('/uvoz', [UvozController::class, 'index'])->name('uvoz');
        Route::post('/uvoz/trading212', [UvozController::class, 'trading212'])->name('uvoz.trading212');
        Route::post('/uvoz/kursevi', [UvozController::class, 'kursevi'])->name('uvoz.kursevi');
        Route::post('/kursevi/osvezi', [UvozController::class, 'osveziKurseve'])->name('kursevi.osvezi');
        Route::delete('/transakcije', [UvozController::class, 'obrisiSve'])->name('transakcije.obrisi');

        Route::get('/izvoz/ppdg-3r', [IzvozController::class, 'ppdg3r'])->name('izvoz.ppdg-3r');
        Route::get('/izvoz/pp-opo/{transakcija}', [IzvozController::class, 'ppOpo'])->whereNumber('transakcija')->name('izvoz.pp-opo');

        Route::get('/profil', [PoreskiProfilController::class, 'edit'])->name('profil');
        Route::put('/profil', [PoreskiProfilController::class, 'update']);
    });
});
