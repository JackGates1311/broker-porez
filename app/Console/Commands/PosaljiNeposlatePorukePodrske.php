<?php

namespace App\Console\Commands;

use App\Models\PorukaPodrske;
use App\Services\PodrskaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('podrska:posalji-neposlate {--limit=50 : Najviše poruka u jednom pokretanju}')]
#[Description('Ponovo šalje mejlom poruke podrške koje nisu otišle (poslato_at je NULL)')]
class PosaljiNeposlatePorukePodrske extends Command
{
    public function handle(PodrskaService $servis): int
    {
        $poruke = PorukaPodrske::query()
            ->whereNull('poslato_at')
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $poslato = 0;

        foreach ($poruke as $poruka) {
            if ($servis->posalji($poruka)) {
                $poslato++;
            } else {
                $this->warn("Poruka #{$poruka->id} ni sada nije poslata (detalji u logu).");
            }
        }

        $neuspesno = $poruke->count() - $poslato;
        $this->info("Poslato: {$poslato}, neuspešno: {$neuspesno}.");

        return $neuspesno > 0 ? self::FAILURE : self::SUCCESS;
    }
}
