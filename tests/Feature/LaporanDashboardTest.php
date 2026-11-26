<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class LaporanDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_report_lists_sp2kp_commodities_requiring_follow_up(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Cabai Rawit Merah', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-01'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Cabai Rawit Merah', 'unit' => 'kg', 'price' => 30000, 'price_date' => '2026-11-03'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Beras Medium', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-01'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Beras Medium', 'unit' => 'kg', 'price' => 11500, 'price_date' => '2026-11-03'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas Aman', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-01'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas Aman', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-03'],
        ]);

        $this->get('/?tab=laporan&region=Situbondo&start_date=2026-11-01&end_date=2026-11-03&date_filter_applied=1')
            ->assertOk()
            ->assertDontSee('id="dashboardFilterForm"', false)
            ->assertSee('Harga yang Perlu Diwaspadai dan Ditinjau Lanjut')
            ->assertSee('Cabai Rawit Merah')
            ->assertSee('Beras Medium')
            ->assertSee('Intervensi')
            ->assertSee('Waspada')
            ->assertSee('Tinjau segera bersama TPID')
            ->assertSee('Pantau perubahan harga')
            ->assertDontSee('Komoditas Aman');
    }

    public function test_report_shows_empty_state_when_no_sp2kp_prices_match_the_period(): void
    {
        $this->get('/?tab=laporan&region=Situbondo&start_date=2026-01-01&end_date=2026-01-31&date_filter_applied=1')
            ->assertOk()
            ->assertSee('Belum ada data harga SP2KP pada filter ini.')
            ->assertSee('Pilih wilayah atau rentang tanggal lain');
    }
}
