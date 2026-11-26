<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class EwsDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_ews_page_uses_sp2kp_prices_to_show_inflation_pressure(): void
    {
        $response = $this->get('/?tab=ews&start_date=2026-11-01&end_date=2026-11-17&date_filter_applied=1');

        $response->assertOk()
            ->assertSee('Data SP2KP')
            ->assertSee('Risiko Inflasi:')
            ->assertSee('Cabai Rawit Merah')
            ->assertSee('32,6%')
            ->assertSee('Rp 57.000/kg')
            ->assertDontSee('Stok (20%)');
    }

    public function test_ews_page_uses_uploaded_sp2kp_prices_for_the_selected_market_and_period(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-01'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 11000, 'price_date' => '2026-11-02'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 13000, 'price_date' => '2026-11-03'],
            ['market_name' => 'Wilayah Lain', 'commodity_name' => 'Komoditas SP2KP', 'unit' => 'kg', 'price' => 99000, 'price_date' => '2026-11-03'],
        ]);

        $this->get('/?tab=ews&region=Situbondo&start_date=2026-11-01&end_date=2026-11-03&date_filter_applied=1&commodity=Komoditas%20SP2KP')
            ->assertOk()
            ->assertSee('Komoditas SP2KP')
            ->assertSee('Rp 13.000/kg')
            ->assertSee('30,0%')
            ->assertSee('Risiko Inflasi: 87 (Intervensi)')
            ->assertDontSee('Rp 99.000/kg');
    }

    public function test_ews_page_explains_when_no_sp2kp_prices_match_the_selected_period(): void
    {
        $response = $this->get('/?tab=ews&start_date=2025-01-01&end_date=2025-01-31&date_filter_applied=1');

        $response->assertOk()
            ->assertSee('Belum ada data SP2KP untuk komoditas dan periode yang dipilih.')
            ->assertSee('Belum ada data');
    }
}
