<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BerandaDashboardTest extends TestCase
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

    public function test_default_date_filter_uses_latest_available_prices_when_current_month_is_empty(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        DB::table('harga_pasar')->insert([
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Beras Medium', 'harga' => 10000, 'tanggal_dibuat' => '2026-10-01'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Beras Medium', 'harga' => 12000, 'tanggal_dibuat' => '2026-10-27'],
        ]);

        try {
            $response = $this->get('/?tab=beranda');

            $response->assertOk()
                ->assertSee('name="start_date" value="2026-11-01"', false)
                ->assertSee('name="end_date" value="2026-11-05"', false)
                ->assertSee('const dbLabels = ["1 Oct"', false)
                ->assertSee('"5 Nov"]', false)
                ->assertSee('10000')
                ->assertSee('12000')
                ->assertSee('Filter Terpasang: 2026-11-01 s/d 2026-11-05')
                ->assertSee('Beras Medium (SIKEPO)');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_date_and_commodity_filters_are_applied_to_the_chart(): void
    {
        DB::table('harga_pasar')->insert([
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Pilihan', 'harga' => 10000, 'tanggal_dibuat' => '2026-10-09'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Pilihan', 'harga' => 11000, 'tanggal_dibuat' => '2026-10-10'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Pilihan', 'harga' => 12000, 'tanggal_dibuat' => '2026-10-20'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Pilihan', 'harga' => 13000, 'tanggal_dibuat' => '2026-10-21'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Lain', 'harga' => 20000, 'tanggal_dibuat' => '2026-10-15'],
        ]);

        $response = $this->get('/?tab=beranda&start_date=2026-10-10&end_date=2026-10-20&commodity=Komoditas%20Pilihan');

        $response->assertOk()
            ->assertSee('const dbLabels = ["10 Oct"', false)
            ->assertSee('"20 Oct"]', false)
            ->assertSee('"label":"Komoditas Pilihan (SIKEPO)"', false)
            ->assertSee('"data":[11000,11000,11000,11000,11000,11000,11000,11000,11000,11000,12000]', false)
            ->assertDontSee('"label":"Komoditas Lain (SIKEPO)"', false);
    }

    public function test_beranda_chart_carries_forward_the_last_price_when_filtered_range_has_no_update(): void
    {
        DB::table('harga_pasar')->insert([
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Pilihan', 'harga' => 10000, 'tanggal_dibuat' => '2026-10-09'],
            ['nama_pasar' => 'Pasar Panji', 'nama_barang' => 'Komoditas Lain', 'harga' => 20000, 'tanggal_dibuat' => '2026-10-09'],
        ]);

        $response = $this->get('/?tab=beranda&start_date=2026-10-10&end_date=2026-10-12&commodity=Komoditas%20Pilihan');

        $response->assertOk()
            ->assertSee('const dbLabels = ["10 Oct","11 Oct","12 Oct"]', false)
            ->assertSee('"label":"Komoditas Pilihan (SIKEPO)"', false)
            ->assertSee('"data":[10000,10000,10000]', false)
            ->assertDontSee('"label":"Komoditas Lain (SIKEPO)"', false);
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
