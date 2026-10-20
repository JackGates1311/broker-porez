<?php

namespace App\Console\Commands;

use App\Mail\IzmenaUslovaMail;
use App\Models\Korisnik;
use App\Support\Pravno;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('pravno:obavesti {--proba : Samo prikaži koliko bi mejlova otišlo} {--limit=250 : Najviše mejlova u jednom pokretanju (dnevni limit SMTP-a)}')]
#[Description('Šalje mejl o novoj verziji Uslova korišćenja i Politike privatnosti korisnicima koji još nisu obavešteni')]
class ObavestiOIzmeniUslova extends Command
{
    /**
     * Može da se pokreće više puta: obavešteni korisnik se pamti (uslovi_obavesten_verzija),
     * pa sledeće pokretanje nastavlja od onih kojima mejl još nije poslat.
     */
    public function handle(): int
    {
        $izmene = array_values(config('pravno.izmene', []));

        if ($izmene === []) {
            $this->error("U config/pravno.php popunite 'izmene' (kratak opis šta se menja) pre slanja.");

            return self::FAILURE;
        }

        $verzija = Pravno::verzija();
        $upit = Korisnik::query()
            ->whereNotNull('email_verifikovan_at')
            ->where(fn ($upit) => $upit->whereNull('uslovi_verzija')->orWhere('uslovi_verzija', '!=', $verzija))
            ->where(fn ($upit) => $upit->whereNull('uslovi_obavesten_verzija')->orWhere('uslovi_obavesten_verzija', '!=', $verzija))
            ->orderBy('id');

        $ukupno = $upit->count();

        if ($this->option('proba')) {
            $this->info("Za obaveštenje o verziji {$verzija} (važi od ".Pravno::vaziOdPrikaz()."): {$ukupno} korisnika. Ništa nije poslato.");

            return self::SUCCESS;
        }

        $poslato = 0;
        $neuspesno = 0;

        foreach ($upit->limit(max(1, (int) $this->option('limit')))->get() as $korisnik) {
            try {
                Mail::to($korisnik->email)->send(new IzmenaUslovaMail($korisnik, $izmene));
                $korisnik->forceFill(['uslovi_obavesten_verzija' => $verzija])->save();
                $poslato++;
            } catch (Throwable $greska) {
                $neuspesno++;
                $this->warn("Korisnik #{$korisnik->id}: {$greska->getMessage()}");
            }
        }

        $preostalo = $ukupno - $poslato;
        $this->info("Poslato: {$poslato}, neuspešno: {$neuspesno}, preostalo: {$preostalo}.");

        if ($preostalo > 0) {
            $this->line('Pokrenite komandu ponovo (npr. sutra, zbog dnevnog limita) da biste nastavili.');
        }

        return $neuspesno > 0 ? self::FAILURE : self::SUCCESS;
    }
}
