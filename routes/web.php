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
use App\Http\Controllers\Porezi\PoreskiObaveznikController;
use App\Http\Controllers\Porezi\UvozController;
use App\Http\Controllers\Porezi\UvozRucniController;
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
        Route::get('/poreski-obaveznik', [PoreskiObaveznikController::class, 'edit'])->name('poreski-obaveznik');
        Route::put('/poreski-obaveznik', [PoreskiObaveznikController::class, 'update']);
    });

    Route::middleware(['verifikovan', 'obaveznik'])->group(function () {
        Route::get('/pocetna', DashboardController::class)->name('pocetna');
        Route::get('/kapitalna-dobit', KapitalnaDobitController::class)->name('kapitalna-dobit');
        Route::get('/dividende', DividendeController::class)->name('dividende');

        Route::get('/uvoz', [UvozController::class, 'index'])->name('uvoz');
        Route::post('/uvoz/trading212', [UvozController::class, 'trading212'])->name('uvoz.trading212');

        Route::controller(UvozRucniController::class)->prefix('/uvoz/rucni')->group(function () {
            Route::post('/', 'zapocni')->name('uvoz.rucni');
            Route::get('/format', 'format')->name('uvoz.rucni.format');
            Route::post('/format', 'sacuvajFormat')->name('uvoz.rucni.format.sacuvaj');
            Route::get('/kolone', 'kolone')->name('uvoz.rucni.kolone');
            Route::post('/kolone', 'sacuvajKolone')->name('uvoz.rucni.kolone.sacuvaj');
            Route::get('/akcije', 'akcije')->name('uvoz.rucni.akcije');
            Route::post('/akcije', 'sacuvajAkcije')->name('uvoz.rucni.akcije.sacuvaj');
            Route::get('/pregled', 'pregled')->name('uvoz.rucni.pregled');
            Route::post('/uvezi', 'uvezi')->name('uvoz.rucni.uvezi');
            Route::post('/odustani', 'odustani')->name('uvoz.rucni.odustani');
        });
        Route::delete('/uvoz/sabloni/{sablon}', [UvozRucniController::class, 'obrisiSablon'])->whereNumber('sablon')->name('uvoz.sabloni.obrisi');

        Route::post('/uvoz/kursevi', [UvozController::class, 'kursevi'])->name('uvoz.kursevi');
        Route::post('/kursevi/osvezi', [UvozController::class, 'osveziKurseve'])->name('kursevi.osvezi');
        Route::delete('/transakcije', [UvozController::class, 'obrisiSve'])->name('transakcije.obrisi');

        Route::get('/izvoz/ppdg-3r', [IzvozController::class, 'ppdg3r'])->name('izvoz.ppdg-3r');
        Route::get('/izvoz/pp-opo/{transakcija}', [IzvozController::class, 'ppOpo'])->whereNumber('transakcija')->name('izvoz.pp-opo');
    });
});
