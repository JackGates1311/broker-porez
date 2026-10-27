<?php

namespace Tests\Feature;

use App\Support\Tabela\Kolona;
use App\Support\Tabela\Tabela;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\TestBaza;
use Tests\TestCase;

class TabelaPretragaTest extends TestCase
{
    use TestBaza;

    protected function setUp(): void
    {
        parent::setUp();

        // Probna tabela za opštu pretragu (nije domenska). TEMPORARY: obična CREATE TABLE bi u MariaDB-u
        // potvrdila transakciju testa; privremena tabela važi samo za ovu konekciju i ne menja šemu.
        DB::statement('DROP TEMPORARY TABLE IF EXISTS stavke');
        Schema::create('stavke', function (Blueprint $tabela) {
            $tabela->temporary();
            $tabela->id();
            $tabela->string('simbol');
            $tabela->integer('broj');
            $tabela->decimal('iznos', 28, 10)->nullable();
            $tabela->dateTime('vreme_utc');
            $tabela->string('akcija');
            $tabela->collation('utf8mb4_general_ci');
        });

        DB::table('stavke')->insert([
            ['simbol' => 'AAPL', 'broj' => 7, 'iznos' => '1519.9600000000', 'vreme_utc' => '2026-03-14 23:30:00', 'akcija' => 'Market buy'],
            ['simbol' => 'IBM', 'broj' => 42, 'iznos' => '-12.3400000000', 'vreme_utc' => '2026-03-14 22:30:00', 'akcija' => 'Market sell'],
            ['simbol' => '10%_X', 'broj' => 7, 'iznos' => null, 'vreme_utc' => '2026-06-01 10:00:00', 'akcija' => 'Limit buy'],
        ]);
    }

    public function test_pretraga_po_tipovima(): void
    {
        $this->assertSame(['AAPL'], $this->simboli(['q' => 'aap']));
        $this->assertSame(['10%_X'], $this->simboli(['q' => '%_']));
        $this->assertSame(['IBM'], $this->simboli(['q' => '42']));
        $this->assertSame(['AAPL'], $this->simboli(['q' => '1.519,9']));
        $this->assertSame(['IBM'], $this->simboli(['q' => '-12,34']));
        // 15.03.2026 po srpskom vremenu počinje 14.03. u 23:00 UTC.
        $this->assertSame(['AAPL'], $this->simboli(['q' => '15.03.2026']));
        $this->assertSame(['IBM'], $this->simboli(['q' => '14.03.2026']));
        $this->assertSame(['IBM'], $this->simboli(['q' => 'prodaja']));
        // OR preko kolona: "7" je broj kod dve stavke.
        $this->assertSame(['10%_X', 'AAPL'], $this->simboli(['q' => '7']));
        $this->assertSame([], $this->simboli(['q' => 'nepostoji']));
    }

    public function test_tekst_se_trazi_bez_razlike_kvacica_i_velikih_slova(): void
    {
        // utf8mb4_general_ci kao u sql/ šemi: „petrovic” nalazi „Petrović” (SQLite to nije umeo).
        DB::table('stavke')->insert(['simbol' => 'Petrović DOO', 'broj' => 1, 'iznos' => '1', 'vreme_utc' => '2026-01-01 10:00:00', 'akcija' => 'Deposit']);

        $this->assertSame(['Petrović DOO'], $this->simboli(['q' => 'petrovic']));
        $this->assertSame(['Petrović DOO'], $this->simboli(['q' => 'PETROVIĆ']));
    }

    public function test_sortiranje_jedna_i_vise_kolona_null_na_kraju(): void
    {
        $this->assertSame(['IBM', 'AAPL', '10%_X'], $this->simboli(['sort' => 'iznos:asc']));
        $this->assertSame(['AAPL', 'IBM', '10%_X'], $this->simboli(['sort' => 'iznos:desc']));
        $this->assertSame(['10%_X', 'AAPL', 'IBM'], $this->simboli(['sort' => 'broj:asc,simbol:asc']));
        $this->assertSame(['AAPL', '10%_X', 'IBM'], $this->simboli(['sort' => 'tip:asc,vreme:asc']));
        // Nepoznata kolona se ignoriše, važi podrazumevani sort (vreme opadajuće).
        $this->assertSame(['10%_X', 'AAPL', 'IBM'], $this->simboli(['sort' => 'nepostoji:asc']));
    }

    /**
     * @param  array<string, string>  $upit
     * @return list<string>
     */
    private function simboli(array $upit): array
    {
        $tabela = Tabela::od([
            Kolona::tekst('simbol', 'Simbol'),
            Kolona::ceo('broj', 'Broj'),
            Kolona::decimalni('iznos', 'Iznos'),
            Kolona::datum('vreme', 'Vreme')->izraz('vreme_utc'),
            Kolona::enumeracija('tip', 'Tip')
                ->opcija('KUPOVINA', 'Kupovina', "akcija LIKE '% buy'")
                ->opcija('PRODAJA', 'Prodaja', "akcija LIKE '% sell'"),
        ])->podrazumevano('vreme', 'desc')->tiebreak('id')->izUpita($upit);

        return $tabela->primeni(DB::table('stavke'))->pluck('simbol')->all();
    }
}
