<?php

namespace Tests\Feature;

use App\Models\AnalysisRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AnalysisDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_analysis_page_reads_saved_model_results_and_forecasts(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Cabai Rawit Merah',
                'unit' => 'kg',
                'price' => 57000,
                'price_date' => '2026-11-01',
            ],
            [
                'market_name' => 'Situbondo',
                'commodity_name' => 'Cabai Rawit Merah',
                'unit' => 'kg',
                'price' => 62000,
                'price_date' => '2026-11-02',
            ],
        ]);

        $run = AnalysisRun::create([
            'model_name' => 'Model Uji',
            'model_accuracy' => 91.25,
            'forecast_horizon_days' => 7,
            'projected_peak_date' => '2026-11-03',
            'expected_inflation_percent' => 0.345,
            'main_factor' => 'Permintaan',
            'factor_correlation_percent' => 82,
            'insight' => 'Insight dari database',
            'generated_at' => now(),
        ]);
        $run->forecasts()->create([
            'commodity_name' => 'Cabe Rawit Merah',
            'forecast_date' => '2026-11-03',
            'predicted_price' => 65000,
        ]);

        $response = $this->get('/?tab=analisis');

        $response->assertOk()
            ->assertSee('Model Uji')
            ->assertSee('Analisis Harga Komoditas SP2KP')
            ->assertSee('Cabai Rawit Merah')
            ->assertSee('91,3%')
            ->assertSee('03 Nov 2026')
            ->assertSee('0,35%')
            ->assertSee('Insight dari database')
            ->assertSee('"Harga Aktual SP2KP - Situbondo"', false)
            ->assertSee('const analysisActualPrices = [57000,62000,null]', false)
            ->assertSee('65000');
    }

    public function test_analysis_page_does_not_invent_forecasts_when_no_run_exists(): void
    {
        $response = $this->get('/?tab=analisis');

        $response->assertOk()
            ->assertSee('Belum ada hasil evaluasi')
            ->assertSee('Belum ada insight analisis yang tersimpan.')
            ->assertSee('Analisis Harga Komoditas SP2KP')
            ->assertSee('Harga Aktual SP2KP - Situbondo')
            ->assertDontSee('Random Forest / KNN')
            ->assertDontSee('94,2%');
    }
}
