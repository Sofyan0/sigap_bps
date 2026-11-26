<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class IntervensiDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_intervention_priorities_follow_sp2kp_prices_and_selected_period(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Cabai Uji', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-01'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Cabai Uji', 'unit' => 'kg', 'price' => 13000, 'price_date' => '2026-11-03'],
            ['market_name' => 'Wilayah Lain', 'commodity_name' => 'Cabai Uji', 'unit' => 'kg', 'price' => 99000, 'price_date' => '2026-11-03'],
        ]);

        $this->get('/?tab=intervensi&region=Situbondo&start_date=2026-11-01&end_date=2026-11-03&date_filter_applied=1&commodity=Cabai%20Uji')
            ->assertOk()
            ->assertSee('Cabai Uji')
            ->assertSee('Rp 13.000/kg')
            ->assertSee('30,0%')
            ->assertSee('Verifikasi pasokan; pertimbangkan operasi pasar atau GPM.')
            ->assertSee('Prioritas Intervensi')
            ->assertSee('Perubahan harga tidak membuktikan dampak program.')
            ->assertDontSee('Rp 99.000/kg')
            ->assertDontSee('15,2 Ton')
            ->assertDontSee('84% (Efektif)');
    }
}
