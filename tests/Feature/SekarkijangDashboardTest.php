<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SekarkijangDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_sekarkijang_displays_excel_style_weekly_iph_summary(): void
    {
        $response = $this->get('/?tab=sekarkijang');

        $response->assertOk()
            ->assertSee('Rekapitulasi Nilai Perubahan IPH Mingguan')
            ->assertSee('M4 September 2026')
            ->assertSee('M5 Oktober 2026')
            ->assertSee('Kabupaten Situbondo')
            ->assertSee('1,13')
            ->assertSee('0,27')
            ->assertSee('id="iphWeeklyChart"', false)
            ->assertSee('[1.13,0.8,0.65,0.77,0.62,0.27]', false)
            ->assertSee('Perubahan IPH (%)')
            ->assertSee('Kota Probolinggo')
            ->assertSee('Rata-rata 4 Kab. Tapal Kuda')
            ->assertSee('Provinsi Jawa Timur')
            ->assertDontSee('diestimasi dari data bulanan');
    }

    public function test_sekarkijang_week_filter_stays_selected_after_filtering_dates(): void
    {
        $response = $this->get('/?tab=sekarkijang&start_date=2026-10-29&end_date=2026-10-31');

        $response->assertOk()
            ->assertSee('<option value="5" selected>Minggu Kelima</option>', false);
    }

    public function test_sekarkijang_comparison_dates_extend_to_today_after_latest_data_fallback(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-16 12:00:00'));
        $latestDate = now()->subMonth()->endOfMonth()->toDateString();
        DB::table('harga_pasar')->insert([
            'nama_pasar' => 'Pasar Rakyat Panji',
            'nama_barang' => 'Beras Medium',
            'harga' => 12000,
            'tanggal_dibuat' => $latestDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $monthStart = \Carbon\Carbon::parse($latestDate)->startOfMonth()->toDateString();

        $response = $this->get('/?tab=sekarkijang');

        $response->assertOk()
            ->assertSee('id="sekarkijangStartDate" type="date" name="start_date" value="' . $monthStart . '"', false)
            ->assertSee('id="sekarkijangEndDate" type="date" name="end_date" value="' . now()->toDateString() . '"', false)
            ->assertSee('"16 Nov"', false);
    }

    public function test_sekarkijang_compares_region_prices_with_sp2kp_simulation(): void
    {
        $records = [];
        for ($index = 1; $index <= 21; $index++) {
            $name = sprintf('Komoditas %02d', $index);
            $latestPrice = 9000 + ($index * 1000);
            $records[] = [
                'nama_pasar' => 'Pasar Rakyat Panji',
                'nama_barang' => $name,
                'harga' => $index === 1 ? 999999 : 8000,
                'tanggal_dibuat' => '2026-10-26',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $records[] = [
                'nama_pasar' => 'Pasar Rakyat Panji',
                'nama_barang' => $name,
                'harga' => $latestPrice,
                'tanggal_dibuat' => '2026-10-27',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('harga_pasar')->insert($records);

        $response = $this->get('/?tab=sekarkijang&start_date=2026-10-01&end_date=2026-10-31');

        $response->assertOk()
            ->assertSee('Perbandingan Harga Antar Kabupaten/Kota Sekarkijang - SP2KP')
            ->assertSee('id="sekarkijangRegionComparisonChart"', false)
            ->assertSee('Kabupaten Situbondo (Pasar Panji +2%)')
            ->assertSee('Kabupaten Banyuwangi')
            ->assertSee('Situbondo menampilkan harga rata-rata Pasar Panji dengan penyesuaian simulasi +2%.')
            ->assertSee('Komoditas 21')
            ->assertSee('26 Oct')
            ->assertSee('27 Oct')
            ->assertSee('<option value="Komoditas 21" selected>Komoditas 21</option>', false)
            ->assertDontSee('20 Komoditas dengan Harga Tertinggi di Pasar Panji')
            ->assertDontSee('<table class="w-full min-w-[520px] text-xs">', false)
            ->assertDontSee('dummy')
            ->assertDontSee('generateDailySeries', false);
    }

    public function test_sekarkijang_shows_an_empty_state_when_the_selected_period_has_no_prices(): void
    {
        $response = $this->get('/?tab=sekarkijang&start_date=2026-01-01&end_date=2026-01-03');

        $response->assertOk()
            ->assertSee('Belum ada data harga untuk komoditas ini sebagai dasar perbandingan.')
            ->assertDontSee('id="sekarkijangRegionComparisonChart"', false);
    }
}
