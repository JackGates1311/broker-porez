<?php

namespace Tests\Unit;

use App\Support\Cirilica;
use PHPUnit\Framework\TestCase;

class DrzaveZaObrazacTest extends TestCase
{
    public function test_preslovljava_dvoslove_i_velika_slova(): void
    {
        $this->assertSame('Република Србија', Cirilica::izLatinice('Republika Srbija'));
        $this->assertSame('Сједињене Америчке Државе', Cirilica::izLatinice('Sjedinjene Američke Države'));
        $this->assertSame('Џибути, Љубљана, Њујорк, ЏЕП', Cirilica::izLatinice('Džibuti, Ljubljana, Njujork, DŽEP'));
    }

    public function test_zvanicni_nazivi_postoje_u_listi_drzava_i_preslovljavaju_se_bez_ostatka_latinice(): void
    {
        $drzave = require __DIR__.'/../../config/drzave.php';
        $zvanicno = require __DIR__.'/../../config/drzave_zvanicno.php';

        $this->assertSame([], array_diff_key($zvanicno, $drzave), 'Zvaničan naziv za nepoznatu oznaku');
        $this->assertSame('Republika Srbija', $zvanicno['RS']);

        foreach ([...$zvanicno, ...$drzave] as $oznaka => $naziv) {
            $this->assertDoesNotMatchRegularExpression('/[A-Za-zČĆŽŠĐčćžšđ]/u', Cirilica::izLatinice($naziv), "{$oznaka}: {$naziv}");
        }
    }
}
