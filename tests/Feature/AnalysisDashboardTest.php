<?php

namespace Tests\Feature;

use App\Models\AnalysisRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalysisDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_page_reads_saved_model_results_and_forecasts(): void
    {
        DB::table('harga_pasar')->insert([
            [
                'nama_pasar' => 'Pasar Panji',
                'nama_barang' => 'Cabe Rawit Merah',
                'harga' => 62000,
                'tanggal_dibuat' => '2026-10-27',
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
            ->assertSee('91,3%')
            ->assertSee('03 Nov 2026')
            ->assertSee('0,35%')
            ->assertSee('Insight dari database')
            ->assertSee('65000');
    }

    public function test_analysis_page_does_not_invent_forecasts_when_no_run_exists(): void
    {
        $response = $this->get('/?tab=analisis');

        $response->assertOk()
            ->assertSee('Belum ada hasil evaluasi')
            ->assertSee('Belum ada insight analisis yang tersimpan.')
            ->assertDontSee('Random Forest / KNN')
            ->assertDontSee('94,2%');
    }
}
