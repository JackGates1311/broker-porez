<?php

namespace App\Services\Porezi;

use Carbon\CarbonImmutable;

/**
 * Obračunski period po srpskom vremenu: cela istorija, godina ili polugodište.
 * Zapis u URL-u: "sve", "2026", "2026-H1", "2026-H2".
 */
final readonly class Period
{
    private const ZONA = 'Europe/Belgrade';

    private function __construct(
        public string $kod,
        public ?CarbonImmutable $od,
        public ?CarbonImmutable $do,
        public ?int $godina,
        public ?int $polugodiste,
    ) {}

    public static function izKoda(?string $kod): self
    {
        if ($kod !== null && preg_match('/^(\d{4})(?:-H([12]))?$/', $kod, $m)) {
            $godina = (int) $m[1];
            $polugodiste = isset($m[2]) ? (int) $m[2] : null;

            return self::za($godina, $polugodiste);
        }

        return new self('sve', null, null, null, null);
    }

    public static function za(int $godina, ?int $polugodiste = null): self
    {
        $pocetak = CarbonImmutable::create($godina, 1, 1, 0, 0, 0, self::ZONA);

        [$od, $do] = match ($polugodiste) {
            1 => [$pocetak, $pocetak->setMonth(6)->endOfMonth()],
            2 => [$pocetak->setMonth(7), $pocetak->endOfYear()],
            default => [$pocetak, $pocetak->endOfYear()],
        };

        return new self(
            $polugodiste ? "{$godina}-H{$polugodiste}" : (string) $godina,
            $od->startOfDay(),
            $do->endOfDay(),
            $godina,
            $polugodiste,
        );
    }

    public function jePolugodiste(): bool
    {
        return $this->polugodiste !== null;
    }

    public function naziv(): string
    {
        return match (true) {
            $this->godina === null => 'Cela istorija',
            $this->polugodiste === 1 => "I polugodište {$this->godina}.",
            $this->polugodiste === 2 => "II polugodište {$this->godina}.",
            default => "{$this->godina}. godina",
        };
    }

    /**
     * Granice perioda u UTC-u, za poređenje sa kolonom vreme_utc.
     *
     * @return array{0: string, 1: string}|null
     */
    public function utcGranice(): ?array
    {
        if ($this->od === null || $this->do === null) {
            return null;
        }

        return [
            $this->od->utc()->format('Y-m-d H:i:s'),
            $this->do->utc()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * PPDG-3R: datum ostvarivanja je poslednji dan polugodišta, dospelost 30 dana kasnije.
     */
    public function datumDospelosti(): ?CarbonImmutable
    {
        return $this->do?->startOfDay()->addDays(30);
    }
}
