<?php

namespace Tests\Feature;

use App\Models\Kurs;
use App\Services\Kursevi\NbsKursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\DomenskeTabele;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class NbsKursServiceTest extends TestCase
{
    use DomenskeTabele;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->napraviDomenskeTabele();
    }

    public function test_preuzima_datume_koji_nedostaju_a_neuspesne_vraca(): void
    {
        Kurs::query()->insert(['datum' => '2026-03-10', 'valuta' => 'USD', 'srednji_kurs' => '108.0000000000']);

        Http::fake([
            '*/rates/2026-03-11' => Http::response(['rates' => [
                ['code' => 'USD', 'exchange_middle' => 107.5, 'parity' => 1],
                ['code' => 'JPY', 'exchange_middle' => 72.5, 'parity' => 100],
            ]]),
            '*/rates/2026-03-12' => Http::response('', 503),
        ]);

        $neuspesni = app(NbsKursService::class)->obezbediKurseve([
            ['2026-03-10', 'USD'],
            ['2026-03-11', 'USD'],
            ['2026-03-11', 'JPY'],
            ['2026-03-11', 'RSD'],
            ['2026-03-12', 'GBX'],
        ]);

        $this->assertSame(['2026-03-12'], $neuspesni);

        // 2026-03-10 je već u bazi; 2026-03-12 je pokušan dva puta (retry).
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/rates/2026-03-10'));
        Http::assertSentCount(3);

        $servis = app(NbsKursService::class);
        $this->assertSame('107.5000000000', (string) $servis->kurs('USD', '2026-03-11'));
        $this->assertSame('0.7250000000', (string) $servis->kurs('JPY', '2026-03-11'));
        $this->assertNull($servis->kurs('GBX', '2026-03-12'));
    }

    public function test_bez_parova_ne_salje_zahteve(): void
    {
        Http::fake();

        $this->assertSame([], app(NbsKursService::class)->obezbediKurseve([['2026-03-11', 'RSD']]));
        Http::assertNothingSent();
    }
}
