<?php

namespace App\Console\Commands;

use App\Mail\ZadovoljstvoIzvestajMail;
use App\Models\OcenaZadovoljstva;
use App\Services\ZadovoljstvoIzvestaj;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

#[Signature('zadovoljstvo:izvestaj {--period=prosli-mesec : prosli-mesec, ovaj-mesec ili godina i mesec (npr. 2026-10)} {--mejl : Pošalji izveštaj na adresu podrške umesto samo ispisa}')]
#[Description('Statistika ocena zadovoljstva insajdera za mesec, uz poređenje sa prethodnim')]
class IzvestajZadovoljstva extends Command
{
    public function handle(ZadovoljstvoIzvestaj $izvestaj): int
    {
        try {
            $mesec = ZadovoljstvoIzvestaj::mesec((string) $this->option('period'));
        } catch (InvalidArgumentException $greska) {
            $this->error($greska->getMessage());

            return self::FAILURE;
        }

        $podaci = $izvestaj->za($mesec);
        $sazetak = $podaci['sazetak'];
        $prethodni = $podaci['prethodni'];
        $broj = fn (?float $vrednost) => $vrednost === null ? '–' : number_format($vrednost, 2, ',', '.');
        $procenat = fn (?int $vrednost) => $vrednost === null ? '–' : "{$vrednost} %";

        $this->info('Zadovoljstvo insajdera za '.ZadovoljstvoIzvestaj::nazivMeseca($mesec));
        $this->table(['', 'Ovaj mesec', 'Prethodni'], [
            ['Odgovora', $sazetak['broj'], $prethodni['broj']],
            ['Prosečna ocena', $broj($sazetak['prosek']), $broj($prethodni['prosek'])],
            ['Zadovoljnih (4–5)', $procenat($sazetak['zadovoljnih']), $procenat($prethodni['zadovoljnih'])],
        ]);

        if ($sazetak['broj'] > 0) {
            $this->table(['Ocena', 'Odgovora'], array_map(fn (int $ocena) => [$ocena, $sazetak['raspodela'][$ocena]], range(5, 1)));
            $this->table(['Obrazac', 'Odgovora', 'Prosek'], array_map(
                fn (string $povod, array $red) => [OcenaZadovoljstva::POVODI[$povod], $red['broj'], $broj($red['prosek'])],
                array_keys($sazetak['po_povodu']),
                $sazetak['po_povodu'],
            ));

            foreach ($podaci['komentari'] as $komentar) {
                $this->line("[{$komentar['ocena']}] ".($komentar['korisnicko_ime'] ?? 'obrisan nalog').', '.$komentar['kreirano_at']->format('d.m.Y.').": {$komentar['komentar']}");
            }
        }

        if (! $this->option('mejl')) {
            return self::SUCCESS;
        }

        if ($sazetak['broj'] === 0) {
            $this->line('Nema odgovora u ovom mesecu, mejl nije poslat.');

            return self::SUCCESS;
        }

        Mail::to(config('podrska.email'))->send(new ZadovoljstvoIzvestajMail($podaci));
        $this->info('Izveštaj je poslat na '.config('podrska.email').'.');

        return self::SUCCESS;
    }
}
