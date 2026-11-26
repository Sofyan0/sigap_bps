<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class BerandaDashboardTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_beranda_summary_uses_market_prices_in_the_selected_date_range(): void
    {
        DB::table('harga_pasar')->insert([
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Naik', 'harga' => 100, 'tanggal_dibuat' => '2026-10-01'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Naik', 'harga' => 110, 'tanggal_dibuat' => '2026-10-27'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Turun', 'harga' => 200, 'tanggal_dibuat' => '2026-10-01'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Turun', 'harga' => 180, 'tanggal_dibuat' => '2026-10-27'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Stabil', 'harga' => 50, 'tanggal_dibuat' => '2026-10-01'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Stabil', 'harga' => 50, 'tanggal_dibuat' => '2026-10-27'],
            ['nama_pasar' => 'Pasar Lain', 'nama_barang' => 'Tidak dihitung', 'harga' => 100, 'tanggal_dibuat' => '2026-10-01'],
        ]);

        $response = $this->get('/?tab=beranda&start_date=2026-10-01&end_date=2026-10-27');

        $response->assertOk()
            ->assertSee('Rata-rata Perubahan Harga')
            ->assertSee('1 Komoditas')
            ->assertSee('0,00%')
            ->assertSee('3 Komoditas');
    }

    public function test_beranda_reports_empty_database_summary_without_fake_totals(): void
    {
        $response = $this->get('/?tab=beranda');

        $response->assertOk()
            ->assertSee('Belum ada data pembanding')
            ->assertSee('Tidak ada data harga untuk komoditas ini pada rentang tanggal terpilih atau sebelum tanggal mulai.')
            ->assertDontSee('id="priceChart"', false)
            ->assertSee('0 Komoditas')
            ->assertDontSee('41 Komoditas')
            ->assertDontSee('8 Komoditas');
    }

    public function test_default_date_filter_uses_the_current_month_through_today(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');

        try {
            $response = $this->get('/?tab=beranda');

            $response->assertOk()
                ->assertSee('name="start_date" value="2026-11-01"', false)
                ->assertSee('name="end_date" value="2026-11-05"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_default_date_filter_chart_uses_source_database_prices_in_the_selected_range(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        DB::table('monitoring_sikepo_prices')->insert([
            ['market_name' => 'Pasar Rakyat Panji', 'commodity_name' => 'Beras Medium', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-11-02'],
        ]);
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Beras Medium', 'unit' => 'kg', 'price' => 12000, 'price_date' => '2026-11-03'],
        ]);

        try {
            $response = $this->get('/?tab=beranda');

            $response->assertOk()
                ->assertSee('name="start_date" value="2026-11-01"', false)
                ->assertSee('name="end_date" value="2026-11-05"', false)
                ->assertSee('const priceSourceChartLabels = ["2 Nov","3 Nov"]', false)
                ->assertSee('"label":"Beras Medium (SIKEPO)"', false)
                ->assertSee('"label":"Beras Medium (SP2KP)"', false)
                ->assertSee('Filter: 2026-11-01 s/d 2026-11-05');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_date_and_commodity_filters_are_applied_to_the_chart(): void
    {
        DB::table('monitoring_sikepo_prices')->insert([
            ['market_name' => 'Pasar Rakyat Panji', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 11000, 'price_date' => '2026-10-10'],
            ['market_name' => 'Pasar Rakyat Panji', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 12000, 'price_date' => '2026-10-20'],
            ['market_name' => 'Pasar Rakyat Panji', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 13000, 'price_date' => '2026-10-21'],
            ['market_name' => 'Pasar Rakyat Panji', 'commodity_name' => 'Komoditas Lain', 'unit' => 'kg', 'price' => 20000, 'price_date' => '2026-10-15'],
        ]);
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 11500, 'price_date' => '2026-10-10'],
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 12500, 'price_date' => '2026-10-20'],
        ]);

        $response = $this->get('/?tab=beranda&start_date=2026-10-10&end_date=2026-10-20&commodity=Komoditas%20Pilihan');

        $response->assertOk()
            ->assertSee('const priceSourceChartLabels = ["10 Oct","20 Oct"]', false)
            ->assertSee('"label":"Komoditas Pilihan (SIKEPO)"', false)
            ->assertSee('"label":"Komoditas Pilihan (SP2KP)"', false)
            ->assertSee('"data":[11000,12000]', false)
            ->assertSee('"data":[11500,12500]', false)
            ->assertDontSee('"label":"Komoditas Lain (SIKEPO)"', false);
    }

    public function test_beranda_chart_does_not_carry_forward_prices_without_a_database_record(): void
    {
        DB::table('monitoring_sikepo_prices')->insert([
            ['market_name' => 'Pasar Rakyat Panji', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 10000, 'price_date' => '2026-10-10'],
        ]);
        DB::table('monitoring_sp2kp_prices')->insert([
            ['market_name' => 'Situbondo', 'commodity_name' => 'Komoditas Pilihan', 'unit' => 'kg', 'price' => 12000, 'price_date' => '2026-10-12'],
        ]);

        $response = $this->get('/?tab=beranda&start_date=2026-10-10&end_date=2026-10-12&commodity=Komoditas%20Pilihan');

        $response->assertOk()
            ->assertSee('const priceSourceChartLabels = ["10 Oct","12 Oct"]', false)
            ->assertSee('"label":"Komoditas Pilihan (SIKEPO)"', false)
            ->assertSee('"label":"Komoditas Pilihan (SP2KP)"', false)
            ->assertSee('"data":[10000,null]', false)
            ->assertSee('"data":[null,12000]', false);
    }

    public function test_sikepo_monitoring_uses_excel_prices_for_table_and_daily_chart(): void
    {
        $response = $this->get('/?tab=monitoring_harga&source=sikeppo&start_date=2026-11-01&end_date=2026-11-17&date_filter_applied=1&commodity=Cabe%20Rawit%20Merah');

        $response->assertOk()
            ->assertSee('Harga harian SIKEPO Pasar Rakyat Panji berdasarkan file 1-17 November 2026')
            ->assertSee('Rp 56.000')
            ->assertSee('17 Nov 2026')
            ->assertSee('<option value="Cabe Rawit Merah" class="bg-white dark:bg-slate-800 text-slate-800 dark:text-white" selected>', false)
            ->assertSee('<option value="Wortel"', false)
            ->assertSee('"label":"Cabe Rawit Merah (SIKEPO)","data":[40000,43000,43000,45000,45000,45000,57000,57000,50000,50000,52000,50000,50000,null,60000,57000,56000]', false)
            ->assertDontSee('"label":"Beras Medium (Bulog) (SIKEPO)"', false);
    }

    public function test_sp2kp_monitoring_uses_excel_prices_for_table_and_daily_chart(): void
    {
        $response = $this->get('/?tab=monitoring_harga&source=sp2kp&start_date=2026-11-01&end_date=2026-11-17&date_filter_applied=1&commodity=Cabai%20Rawit%20Merah');

        $response->assertOk()
            ->assertSee('Harga harian berdasarkan laporan SP2KP Situbondo, 1-17 November 2026')
            ->assertSee('Rp 57.000')
            ->assertSee('16 Nov 2026')
            ->assertSee('<option value="Cabai Rawit Merah" class="bg-white dark:bg-slate-800 text-slate-800 dark:text-white" selected>', false)
            ->assertSee('<option value="Kacang Hijau"', false)
            ->assertSee('"label":"Cabai Rawit Merah (SP2KP)","data":[null,43000,43000,45000,45000,45000,null,null,50000,50000,52000,50000,50000,null,null,57000,null]', false)
            ->assertDontSee('"label":"Beras Medium (SP2KP)"', false)
            ->assertDontSee('Kemendag Dummy');
    }

    public function test_sikepo_monitoring_does_not_replace_missing_excel_dates_with_database_prices(): void
    {
        DB::table('harga_pasar')->insert([
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Cabe Merah Besar', 'harga' => 99999, 'tanggal_dibuat' => '2026-11-14'],
        ]);

        $response = $this->get('/?tab=monitoring_harga&source=sikeppo&start_date=2026-11-01&end_date=2026-11-17&date_filter_applied=1&commodity=Cabe%20Merah%20Besar');

        $response->assertOk()
            ->assertSee('id="chartHargaTren"', false)
            ->assertSee('"label":"Cabe Merah Besar (SIKEPO)"', false)
            ->assertSee('"data":[30000,30000,30000,29000,29000,29000,28000,28000,30000,30000,30000,30000,30000,null,30000,30000,30000]', false)
            ->assertDontSee('99999');
    }

    public function test_monitoring_chart_explains_when_selected_date_range_has_no_prices(): void
    {
        DB::table('harga_pasar')->insert([
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Pilihan', 'harga' => 10000, 'tanggal_dibuat' => '2026-10-10'],
        ]);

        $response = $this->get('/?tab=monitoring_harga&start_date=2026-10-01&end_date=2026-10-03&date_filter_applied=1&commodity=Komoditas%20Pilihan');

        $response->assertOk()
            ->assertSee('Tidak ada data harga SIKEPPO pada rentang tanggal ini maupun sebelum tanggal mulai.')
            ->assertDontSee('id="chartHargaTren"', false);
    }
}
