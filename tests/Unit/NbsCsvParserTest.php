<?php

namespace Tests\Unit;

use App\Services\Kursevi\NbsCsvParser;
use BcMath\Number;
use PHPUnit\Framework\TestCase;

class NbsCsvParserTest extends TestCase
{
    public function test_cita_srednji_kurs_po_datumu_primene_i_paritetu(): void
    {
        $putanja = $this->fajl(<<<'CSV'
            Датум формирања, Датум примене, Валута, Назив земље, Ознака, Важи за, Средњи курс
            06.06.2025,08.06.2025,978,EMU,EUR,1,117.1854
            09.06.2025,09.06.2025,392,Japan,JPY,100,70.8950
            CSV);

        $kursevi = (new NbsCsvParser)->parsiraj($putanja);
        unlink($putanja);

        $this->assertSame('2025-06-08', $kursevi[0]['datum']);
        $this->assertSame('EUR', $kursevi[0]['valuta']);
        $this->assertEquals(new Number('117.1854'), $kursevi[0]['kurs']);
        $this->assertEquals(new Number('0.70895'), $kursevi[1]['kurs']);
    }

    public function test_lista_bez_srednjeg_kursa_se_odbija(): void
    {
        $putanja = $this->fajl(<<<'CSV'
            Бр курсне листе, Датум, Валута, Назив земље, Ознака, Важи за, Куповни, Продајни
            46,13.03.2026,978,EMU,EUR,1,117.0615,117.7659
            CSV);

        try {
            $this->expectException(\InvalidArgumentException::class);
            (new NbsCsvParser)->parsiraj($putanja);
        } finally {
            unlink($putanja);
        }
    }

    private function fajl(string $sadrzaj): string
    {
        $putanja = tempnam(sys_get_temp_dir(), 'nbs');
        file_put_contents($putanja, $sadrzaj);

        return $putanja;
    }
}
