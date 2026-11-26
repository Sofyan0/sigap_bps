<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PriceSourceComparisonDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_dashboard_compares_sources_for_the_latest_shared_commodity_date(): void
    {
        DB::table('monitoring_sikepo_prices')->insert([
            [
                'market_name' => 'Pasar Rakyat Panji',
                'commodity_name' => 'Cabe Rawit Merah',
                'unit' => 'kg',
                'price' => 10000,
                'price_date' => '2026-11-01',
            ],
            [
                'market_name' => 'Pasar Rakyat Panji',
                'commodity_name' => 'Cabe Rawit Merah',
                'unit' => 'kg',
                'price' => 12000,
                'price_date' => '2026-11-03',
            ],
            [
                'market_name' => 'Pasar Rakyat Panji',
                'commodity_name' => 'Beras Medium',
                'unit' => 'kg',
                'price' => 12500,
                'price_date' => '2026-11-02',
            ],
            [
                'market_name' => 'Pasar Rakyat Panji',
                'commodity_name' => 'Telur Ayam',
                'unit' => 'kg',
                'price' => 30000,
                'price_date' => '2026-11-02',
            ],
            [
                'market_name' => 'Pasar Rakyat Panji',
                'commodity_name' => 'Komoditas SIKEPO Saja',
                'unit' => 'kg',
                'price' => 9000,
                'price_date' => '2026-11-02',
            ],
            [
                'market_name' => 'Pasar Rakyat Panji',
                'commodity_name' => 'Ikan Uji',
                'unit' => 'kg',
                'price' => 10000,
                'price_date' => '2026-11-01',
            ],
            [
                'market_name' => 'Pasar Rakyat Lain',
                'commodity_name' => 'Cabe Rawit Merah',
                'unit' => 'kg',
                'price' => 99999,
                'price_date' => '2026-11-01',
            ],
        ]);
        DB::table('monitoring_sp2kp_prices')->insert([
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Cabai Rawit Merah',
                'unit' => 'kg',
                'price' => 12000,
                'price_date' => '2026-11-01',
            ],
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Cabai Rawit Merah',
                'unit' => 'kg',
                'price' => 15000,
                'price_date' => '2026-11-04',
            ],
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Beras Medium',
                'unit' => 'kg',
                'price' => 11500,
                'price_date' => '2026-11-02',
            ],
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Telur Ayam',
                'unit' => 'butir',
                'price' => 2000,
                'price_date' => '2026-11-02',
            ],
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Ikan Uji',
                'unit' => 'kg',
                'price' => 12000,
                'price_date' => '2026-11-02',
            ],
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Komoditas Tanpa Pasangan',
                'unit' => 'kg',
                'price' => 13000,
                'price_date' => '2026-11-02',
            ],
        ]);

        $this->get('/?tab=beranda&start_date=2026-11-01&end_date=2026-11-05&date_filter_applied=1')
            ->assertOk()
            ->assertSee('Perbandingan Harga SIKEPO dan SP2KP')
            ->assertSee('Cabai Rawit Merah')
            ->assertSee('01/11/2026')
            ->assertSee('+Rp 2.000')
            ->assertSee('-Rp 1.000')
            ->assertSee('Satuan berbeda')
            ->assertSee('"label":"Cabai Rawit Merah (SIKEPO)"', false)
            ->assertSee('"label":"Cabai Rawit Merah (SP2KP)"', false)
            ->assertSee('"borderDash":[6,4]', false)
            ->assertSee('>Komoditas Tanpa Pasangan</td>', false)
            ->assertSee('>Komoditas SIKEPO Saja</td>', false)
            ->assertSee('Tanggal berbeda')
            ->assertSee('Data tidak tersedia')
            ->assertDontSee('99.999')
            ->assertDontSee('15.000');
    }

    public function test_dashboard_does_not_use_bundled_files_when_source_tables_are_empty(): void
    {
        $this->get('/?tab=beranda&start_date=2026-11-01&end_date=2026-11-17&date_filter_applied=1&commodity=Cabe%20Rawit%20Merah')
            ->assertOk()
            ->assertSee('Belum ada data komoditas SIKEPO atau SP2KP di database pada rentang terpilih.')
            ->assertDontSee('Cabai Rawit Merah');
    }
}
