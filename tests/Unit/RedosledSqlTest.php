<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Baza\PripremaBaze;

/**
 * sql/redosled.txt je jedini izvor redosleda šeme (test baza i deploy): novi sql/ fajl ne sme da ostane van njega.
 */
class RedosledSqlTest extends TestCase
{
    private string $koren;

    protected function setUp(): void
    {
        $this->koren = dirname(__DIR__, 2);
    }

    public function test_svaki_sql_fajl_je_u_redosledu_ili_izricito_izostavljen(): void
    {
        $navedeni = [...PripremaBaze::redosled($this->koren), ...PripremaBaze::izostavljeni($this->koren)];
        $postojeci = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->koren.'/sql')) as $fajl) {
            if ($fajl->isFile() && $fajl->getExtension() === 'sql') {
                $postojeci[] = substr($fajl->getPathname(), strlen($this->koren) + 1);
            }
        }

        sort($navedeni);
        sort($postojeci);

        $this->assertSame([], array_values(array_diff($postojeci, $navedeni)), 'sql/ fajlovi kojih nema u sql/redosled.txt');
        $this->assertSame([], array_values(array_diff($navedeni, $postojeci)), 'sql/redosled.txt navodi fajlove kojih nema');
    }

    public function test_redosled_nema_duplikata_i_pocinje_osnovnim_tabelama(): void
    {
        $redosled = PripremaBaze::redosled($this->koren);

        $this->assertSame($redosled, array_values(array_unique($redosled)));
        $this->assertSame('sql/broker_proezi_create_tables.sql', $redosled[0]);
    }
}
