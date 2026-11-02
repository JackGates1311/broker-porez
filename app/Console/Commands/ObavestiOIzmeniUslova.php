<?php

namespace App\Console\Commands;

use App\Mail\IzmenaUslovaMail;
use App\Models\Korisnik;
use App\Support\Pravno;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('pravno:obavesti {--proba : Samo prikaži koliko bi mejlova otišlo} {--limit=250 : Najviše mejlova u jednom pokretanju (dnevni limit SMTP-a)}')]
#[Description('Šalje mejl o novoj verziji Uslova korišćenja i Politike privatnosti korisnicima koji još nisu obavešteni')]
class ObavestiOIzmeniUslova extends Command
{
    /** Najmanje dana između obaveštenja i stupanja nove verzije na snagu. */
    public const MIN_DANA_UNAPRED = 15;

    /**
     * Može da se pokreće više puta: obavešteni korisnik se pamti (uslovi_obavesten_verzija),
     * pa sledeće pokretanje nastavlja od onih kojima mejl još nije poslat. Scheduler je pokreće
     * svakog dana (routes/console.php), pa posle deploy-a nove verzije slanje kreće samo.
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
        // round: oko promene letnjeg računanja vremena razlika dva beogradska datuma nije ceo broj dana.
        $danaDoStupanja = (int) round(CarbonImmutable::today('Europe/Belgrade')->diffInDays(Pravno::vaziOd(), false));
        $kasno = $ukupno > 0 && $danaDoStupanja < self::MIN_DANA_UNAPRED;
        $upozorenje = 'Do stupanja na snagu ('.Pravno::vaziOdPrikaz().") ostalo je {$danaDoStupanja} dana, a obaveštenje mora stići bar "
            .self::MIN_DANA_UNAPRED." dana ranije. Pomerite 'vazi_od' u config/pravno.php.";

        if ($this->option('proba')) {
            $this->info("Za obaveštenje o verziji {$verzija} (važi od ".Pravno::vaziOdPrikaz()."): {$ukupno} korisnika. Ništa nije poslato.");

            if ($kasno) {
                $this->warn($upozorenje);
            }

            return self::SUCCESS;
        }

        if ($kasno) {
            $this->error($upozorenje.' Ništa nije poslato.');

            return self::FAILURE;
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
