<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

#[Signature('obaveznik:sifruj')]
#[Description('Šifruje JMBG/PIB, tekući račun i broj pasoša obaveznika koji su upisani pre uvođenja šifrovanja')]
class SifrujObaveznike extends Command
{
    /** Kolone sa 'encrypted' castom u App\Models\PoreskiObaveznik. */
    private const KOLONE = ['pib', 'jmbg_podnosioca', 'tekuci_racun', 'broj_pasosa'];

    /**
     * Može da se pokrene više puta: vrednost koja se već dešifruje preskače se.
     */
    public function handle(): int
    {
        $sifrovano = 0;

        DB::table('poreski_obaveznik')->orderBy('korisnik_id')->each(function (object $red) use (&$sifrovano) {
            $izmene = [];

            foreach (self::KOLONE as $kolona) {
                $vrednost = $red->{$kolona};

                if ($vrednost !== null && ! $this->jeSifrovano($vrednost)) {
                    $izmene[$kolona] = Crypt::encryptString($vrednost);
                }
            }

            if ($izmene !== []) {
                DB::table('poreski_obaveznik')->where('korisnik_id', $red->korisnik_id)->update($izmene);
                $sifrovano++;
            }
        });

        $this->info("Šifrovano obaveznika: {$sifrovano}.");

        return self::SUCCESS;
    }

    private function jeSifrovano(string $vrednost): bool
    {
        try {
            Crypt::decryptString($vrednost);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
