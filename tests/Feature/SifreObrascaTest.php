<?php

namespace Tests\Feature;

use App\Http\Requests\Porezi\Ppdg3rRequest;
use App\Http\Requests\Porezi\PpOpoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SifreObrascaTest extends TestCase
{
    public function test_ppdg3r_prihvata_samo_oznake_iz_uputstva(): void
    {
        $pravila = (new Ppdg3rRequest)->rules();
        $osnova = ['godina' => 2026, 'polugodiste' => 1];

        $this->assertTrue(Validator::make($osnova + ['vrsta_prijave' => '6', 'osnov_za_prijavu' => '4'], $pravila)->passes());
        $this->assertTrue(Validator::make($osnova, $pravila)->passes());

        $greske = Validator::make($osnova + ['vrsta_prijave' => '2', 'osnov_za_prijavu' => '9'], $pravila)->errors();
        $this->assertTrue($greske->has('vrsta_prijave'));
        $this->assertTrue($greske->has('osnov_za_prijavu'));
    }

    public function test_pp_opo_prihvata_samo_oznake_iz_uputstva(): void
    {
        $pravila = (new PpOpoRequest)->rules();

        $this->assertTrue(Validator::make(['vrsta_prijave' => '5', 'nacin_ostvarivanja' => '1', 'sifra_vrste_prihoda' => '123456789'], $pravila)->passes());

        $greske = Validator::make(['vrsta_prijave' => '6', 'nacin_ostvarivanja' => '4', 'sifra_vrste_prihoda' => '12'], $pravila)->errors();
        $this->assertTrue($greske->has('vrsta_prijave'));
        $this->assertTrue($greske->has('nacin_ostvarivanja'));
        $this->assertTrue($greske->has('sifra_vrste_prihoda'));
    }

    public function test_podrazumevane_sifre_su_medju_ponudjenim(): void
    {
        $this->assertArrayHasKey(config('porezi.ppdg3r.vrsta_prijave'), config('porezi.ppdg3r.vrste_prijave'));
        $this->assertArrayHasKey(config('porezi.ppdg3r.osnov_za_prijavu'), config('porezi.ppdg3r.osnovi_za_prijavu'));
        $this->assertArrayHasKey(config('porezi.ppopo.vrsta_prijave'), config('porezi.ppopo.vrste_prijave'));
        $this->assertArrayHasKey(config('porezi.ppopo.nacin_ostvarivanja'), config('porezi.ppopo.nacini_ostvarivanja'));
    }
}
