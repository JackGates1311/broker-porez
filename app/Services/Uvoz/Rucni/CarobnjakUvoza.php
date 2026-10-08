<?php

namespace App\Services\Uvoz\Rucni;

use App\Models\SablonUvoza;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stanje čarobnjaka ručnog uvoza: uploadovan fajl čeka u storage/app/private/uvoz
 * pod UUID imenom, a sesija pamti njegovo ime i podešavanja. Putanja se nikad ne
 * prima iz zahteva, samo iz sesije.
 */
final class CarobnjakUvoza
{
    private const KLJUC = 'uvoz_rucni';

    private const DIREKTORIJUM = 'uvoz';

    /** Napušteni fajlovi stariji od ovoga brišu se pri sledećem pokretanju čarobnjaka. */
    private const ZASTAREVA_POSLE_SEKUNDI = 24 * 60 * 60;

    /**
     * @param  array{fajl: string, naziv: string, podesavanja: array<string, mixed>, sablon_id: ?int, sablon_naziv: ?string}  $stanje
     */
    private function __construct(
        private readonly Session $sesija,
        private array $stanje,
    ) {}

    public static function zapocni(Session $sesija, UploadedFile $fajl, PodesavanjaUvoza $podesavanja, ?SablonUvoza $sablon = null): self
    {
        self::ocistiZastarele();
        self::iz($sesija)?->zavrsi();

        $ime = Str::uuid()->toString().'.csv';
        $fajl->storeAs(self::DIREKTORIJUM, $ime, 'local');

        $carobnjak = new self($sesija, [
            'fajl' => $ime,
            'naziv' => $fajl->getClientOriginalName(),
            'podesavanja' => $podesavanja->uNiz(),
            'sablon_id' => $sablon?->id,
            'sablon_naziv' => $sablon?->naziv,
        ]);
        $carobnjak->zapamti();

        return $carobnjak;
    }

    /**
     * Čarobnjak iz sesije, ili null ako sesija ili fajl više ne postoje.
     */
    public static function iz(Session $sesija): ?self
    {
        $stanje = $sesija->get(self::KLJUC);

        if (! is_array($stanje) || ! is_string($stanje['fajl'] ?? null) || ! preg_match('/^[0-9a-f-]{36}\.csv$/', $stanje['fajl'])) {
            return null;
        }

        if (! self::disk()->exists(self::DIREKTORIJUM.'/'.$stanje['fajl'])) {
            $sesija->forget(self::KLJUC);

            return null;
        }

        return new self($sesija, $stanje);
    }

    public function putanja(): string
    {
        return self::disk()->path(self::DIREKTORIJUM.'/'.$this->stanje['fajl']);
    }

    public function nazivFajla(): string
    {
        return $this->stanje['naziv'];
    }

    public function podesavanja(): PodesavanjaUvoza
    {
        return PodesavanjaUvoza::izNiza($this->stanje['podesavanja']);
    }

    public function sacuvaj(PodesavanjaUvoza $podesavanja): void
    {
        $this->stanje['podesavanja'] = $podesavanja->uNiz();
        $this->zapamti();
    }

    public function sablonId(): ?int
    {
        return $this->stanje['sablon_id'] ?? null;
    }

    public function sablonNaziv(): ?string
    {
        return $this->stanje['sablon_naziv'] ?? null;
    }

    /**
     * Briše privremeni fajl i stanje iz sesije (posle uvoza ili na "Odustani").
     */
    public function zavrsi(): void
    {
        self::disk()->delete(self::DIREKTORIJUM.'/'.$this->stanje['fajl']);
        $this->sesija->forget(self::KLJUC);
    }

    private function zapamti(): void
    {
        $this->sesija->put(self::KLJUC, $this->stanje);
    }

    private static function ocistiZastarele(): void
    {
        $disk = self::disk();
        $granica = time() - self::ZASTAREVA_POSLE_SEKUNDI;

        foreach ($disk->files(self::DIREKTORIJUM) as $fajl) {
            if ($disk->lastModified($fajl) < $granica) {
                $disk->delete($fajl);
            }
        }
    }

    private static function disk(): Filesystem
    {
        return Storage::disk('local');
    }
}
