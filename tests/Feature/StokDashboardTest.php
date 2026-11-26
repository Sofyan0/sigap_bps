<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class StokDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_stock_dashboard_uses_sp2kp_prices_and_does_not_invent_inventory_quantities(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-01'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 12000, 'price_date' => '2026-11-02'],
            ['market_name' => 'Wilayah Lain', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 99000, 'price_date' => '2026-11-02'],
        ]);

        $this->get('/?tab=stok&region=Situbondo&start_date=2026-11-01&end_date=2026-11-02&date_filter_applied=1&commodity=Komoditas%20SP2KP')
            ->assertOk()
            ->assertSee('Komoditas SP2KP')
            ->assertSee('Rp 12.000/kg')
            ->assertSee('20,0%')
            ->assertSee('Tidak tersedia')
            ->assertDontSee('Rp 99.000/kg')
            ->assertDontSee('1.250 Ton')
            ->assertDontSee('62.000 Liter');
    }

    public function test_stock_dashboard_uses_the_bundled_sp2kp_prices_when_no_upload_exists(): void
    {
        $this->get('/?tab=stok&start_date=2026-11-01&end_date=2026-11-17&date_filter_applied=1')
            ->assertOk()
            ->assertSee('Cabai Rawit Merah')
            ->assertSee('Rp 57.000/kg')
            ->assertSee('Tidak tersedia');
    }
}
