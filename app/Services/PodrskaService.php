<?php

namespace App\Services;

use App\Mail\PodrskaMail;
use App\Models\Korisnik;
use App\Models\PorukaPodrske;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PodrskaService
{
    /** Direktorijum priloga na disku local: podrska/{poruka_id}/{uuid}.{ext}. */
    public const DIREKTORIJUM = 'podrska';

    /**
     * Upisuje poruku i priloge, pa šalje mejl podršci. Poruka ostaje sačuvana i kada mejl ne ode
     * (poslato_at = NULL, ponovo je šalje php artisan podrska:posalji-neposlate).
     *
     * @param  array{naslov?: ?string, poruka: string}  $podaci
     * @param  array<int, UploadedFile>  $fajlovi
     */
    public function primi(Korisnik $korisnik, array $podaci, array $fajlovi, ?string $stranica, ?string $userAgent): PorukaPodrske
    {
        $disk = Storage::disk('local');
        $sacuvani = [];

        try {
            $poruka = DB::transaction(function () use ($korisnik, $podaci, $fajlovi, $stranica, $userAgent, $disk, &$sacuvani) {
                $poruka = $korisnik->porukePodrske()->create([
                    'naslov' => filled($podaci['naslov'] ?? null) ? $podaci['naslov'] : null,
                    'poruka' => $podaci['poruka'],
                    'stranica' => self::skrati($stranica),
                    'user_agent' => self::skrati($userAgent),
                ]);

                foreach ($fajlovi as $fajl) {
                    // Ekstenzija i tip po sadržaju fajla, ne po imenu koje je poslao pregledač.
                    $ekstenzija = $fajl->extension();
                    $putanja = $disk->putFileAs(self::DIREKTORIJUM.'/'.$poruka->id, $fajl, Str::uuid().'.'.$ekstenzija);

                    if ($putanja === false) {
                        throw new RuntimeException('Prilog poruke podrške nije sačuvan.');
                    }

                    $sacuvani[] = $putanja;

                    $poruka->prilozi()->create([
                        'korisnik_id' => $korisnik->id,
                        'naziv' => self::naziv($fajl->getClientOriginalName(), $ekstenzija),
                        'putanja' => $putanja,
                        'mime' => (string) $fajl->getMimeType(),
                        'velicina' => (int) $fajl->getSize(),
                    ]);
                }

                return $poruka;
            });
        } catch (Throwable $greska) {
            $disk->delete($sacuvani);

            throw $greska;
        }

        $this->posalji($poruka);

        return $poruka;
    }

    /**
     * Šalje mejl za već sačuvanu poruku; vraća false (i loguje grešku) kada slanje ne uspe.
     */
    public function posalji(PorukaPodrske $poruka): bool
    {
        try {
            Mail::to(config('podrska.email'))->send(new PodrskaMail($poruka));
        } catch (Throwable $greska) {
            Log::error("Poruka podrške #{$poruka->id} nije poslata mejlom.", ['greska' => $greska->getMessage()]);

            return false;
        }

        $poruka->forceFill(['poslato_at' => now()])->save();

        return true;
    }

    /**
     * Ime priloga za mejl: bez putanje i neobičnih znakova, sa ekstenzijom koja odgovara sadržaju.
     */
    private static function naziv(string $original, string $ekstenzija): string
    {
        $ime = preg_replace('/[^\pL\pN ._-]+/u', '_', pathinfo($original, PATHINFO_FILENAME)) ?? '';
        $ime = trim(Str::limit($ime, 200, ''), ' ._');

        return ($ime !== '' ? $ime : 'prilog').'.'.$ekstenzija;
    }

    private static function skrati(?string $vrednost): ?string
    {
        return filled($vrednost) ? Str::limit($vrednost, 500, '') : null;
    }
}
