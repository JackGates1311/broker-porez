<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SifarniciTest extends TestCase
{
    public function test_svaka_drzava_ima_troslovnu_oznaku(): void
    {
        $drzave = require __DIR__.'/../../config/drzave.php';
        $alfa3 = require __DIR__.'/../../config/drzave_alfa3.php';

        $this->assertSame(array_keys($drzave), array_keys($alfa3));
        foreach ($alfa3 as $oznaka => $kod) {
            $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $kod, $oznaka);
        }
        $this->assertSame('SRB', $alfa3['RS']);
        $this->assertSame('DEU', $alfa3['DE']);
        $this->assertCount(count($alfa3), array_unique($alfa3));
    }

    public function test_sifre_opstina_su_trocifrene_i_jedinstvenih_naziva(): void
    {
        $opstine = require __DIR__.'/../../config/opstine.php';

        $this->assertCount(197, $opstine);
        foreach (array_keys($opstine) as $sifra) {
            $this->assertMatchesRegularExpression('/^\d{3}$/', (string) $sifra);
        }
        $this->assertCount(count($opstine), array_unique($opstine));
        $this->assertSame('Grad Novi Sad', $opstine['223']);
        $this->assertSame('Ada', $opstine['201']);
        $this->assertSame('Palilula (Beograd)', $opstine['015']);
    }
}
