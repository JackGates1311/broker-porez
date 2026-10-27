<?php

namespace App\Services;

use App\Models\Korisnik;
use App\Models\PorukaPodrske;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BrisanjeNalogaService
{
    /**
     * Tabele sa podacima korisnika, redom kojim se brišu (deca pre roditelja).
     * U MariaDB-u bi ih obrisao i ON DELETE CASCADE, ali brisanje ne sme da zavisi od toga.
     */
    private const TABELE = [
        'prilozi_poruka_podrske',
        'poruke_podrske',
        'ocene_zadovoljstva',
        'alokacije_lotova_prodaje',
        'poreski_lotovi',
        'transakcije',
        'sabloni_uvoza',
        'poreski_obaveznik',
        'verifikacioni_kodovi',
        'tokeni_reset_lozinke',
    ];

    /**
     * Trajno briše nalog i sve njegove podatke. Ostaje samo anonimni red u obrisani_nalozi
     * (mesec registracije i način prijave), bez ikakve veze sa korisnikom.
     */
    public function obrisi(Korisnik $korisnik): void
    {
        $porukePodrske = $korisnik->porukePodrske()->pluck('id');

        DB::transaction(function () use ($korisnik) {
            DB::table('obrisani_nalozi')->insert([
                'registrovan_mesec' => ($korisnik->kreirano_at ?? now())->format('Y-m'),
                'nacin_prijave' => $korisnik->google_id !== null ? 'google' : 'lozinka',
                'obrisano_at' => now(),
            ]);

            foreach (self::TABELE as $tabela) {
                DB::table($tabela)->where('korisnik_id', $korisnik->id)->delete();
            }

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $korisnik->id)->delete();
            }

            $korisnik->delete();
        });

        // Prilozi poruka podrške su fajlovi; brišu se tek kada su redovi sigurno obrisani.
        foreach ($porukePodrske as $porukaId) {
            Storage::disk('local')->deleteDirectory(PodrskaService::DIREKTORIJUM.'/'.$porukaId);
        }
    }

    /**
     * Svi podaci naloga u mašinski čitljivom obliku (pravo na prenosivost, ZZPL čl. 36 / GDPR čl. 20).
     *
     * @return array<string, mixed>
     */
    public function izvoz(Korisnik $korisnik): array
    {
        $obaveznik = $korisnik->poreskiObaveznik;

        return [
            'izvezeno_at' => now()->toIso8601String(),
            'nalog' => [
                'korisnicko_ime' => $korisnik->korisnicko_ime,
                'email' => $korisnik->email,
                'email_verifikovan_at' => $korisnik->email_verifikovan_at?->toIso8601String(),
                'prijava_google_nalogom' => $korisnik->google_id !== null,
                'kreirano_at' => $korisnik->kreirano_at?->toIso8601String(),
                'uslovi_prihvaceni_at' => $korisnik->uslovi_prihvaceni_at?->toIso8601String(),
                'uslovi_verzija' => $korisnik->uslovi_verzija,
                'povratne_informacije_ukljucene' => $korisnik->jeInsajder(),
            ],
            'poreski_obaveznik' => $obaveznik?->only($obaveznik->getFillable()),
            'transakcije' => DB::table('transakcije')
                ->leftJoin('imovina', 'imovina.id', '=', 'transakcije.imovina_id')
                ->where('transakcije.korisnik_id', $korisnik->id)
                ->orderBy('transakcije.vreme_utc')
                ->orderBy('transakcije.id')
                ->select('transakcije.*', 'imovina.isin', 'imovina.simbol', 'imovina.naziv as naziv_imovine')
                ->get()
                ->map(fn (object $red) => collect((array) $red)->except(['korisnik_id', 'imovina_id', 'jedinstveni_kljuc'])->all())
                ->all(),
            'sabloni_uvoza' => $korisnik->sabloniUvoza()
                ->orderBy('naziv')
                ->get()
                ->map(fn ($sablon) => ['naziv' => $sablon->naziv, 'podesavanja' => $sablon->podesavanja])
                ->all(),
            'poruke_podrske' => $korisnik->porukePodrske()
                ->with('prilozi')
                ->orderBy('kreirano_at')
                ->orderBy('id')
                ->get()
                ->map(fn (PorukaPodrske $poruka) => [
                    'naslov' => $poruka->naslov,
                    'poruka' => $poruka->poruka,
                    'stranica' => $poruka->stranica,
                    'kreirano_at' => $poruka->kreirano_at?->toIso8601String(),
                    'poslato_at' => $poruka->poslato_at?->toIso8601String(),
                    'prilozi' => $poruka->prilozi->pluck('naziv')->all(),
                ])
                ->all(),
            'ocene_zadovoljstva' => $korisnik->oceneZadovoljstva()
                ->orderBy('kreirano_at')
                ->orderBy('id')
                ->get()
                ->map(fn ($ocena) => [
                    'ocena' => $ocena->ocena,
                    'komentar' => $ocena->komentar,
                    'povod' => $ocena->povod,
                    'kreirano_at' => $ocena->kreirano_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
