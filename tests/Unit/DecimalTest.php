<?php

namespace Tests\Unit;

use App\Support\Decimal;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function test_tekst_i_ceo_broj(): void
    {
        $this->assertSame('117.1907000000', (string) Decimal::n('117.1907000000'));
        $this->assertSame('0', (string) Decimal::n(null));
        $this->assertSame('42', (string) Decimal::n(42));
    }

    public function test_float_iz_baze_ne_gubi_decimale(): void
    {
        // Npr. pdo_sqlite ili json_decode daju float; bez posebne obrade PHP ga pretvara u int (102.1777 → 102).
        $this->assertSame('102.1777000000', (string) Decimal::n(102.1777));
        $this->assertSame('3.4000000000', (string) Decimal::n(3.4));
        $this->assertSame('-0.0000100000', (string) Decimal::n(-0.00001));
        $this->assertSame('1000000.0000000000', (string) Decimal::n(1.0E6));
    }
}
