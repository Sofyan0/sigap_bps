<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SekarkijangPriceImportTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_excel_uploads_are_saved_per_region_and_used_in_the_comparison_chart(): void
    {
        $this->post(route('sekarkijang-prices.upload'), [
            'region_code' => '3511',
            'excel_file' => $this->makeExcelUpload([
                ['Komoditas', 'Satuan', '2026-11-01', '2026-11-02', '2026-11-05'],
                ['Beras Uji', 'kg', 12000, 13000, 14000],
            ]),
        ])->assertRedirect();

        $this->post(route('sekarkijang-prices.upload'), [
            'region_code' => '3509',
            'excel_file' => $this->makeExcelUpload([
                ['Komoditas', 'Satuan', '2026-11-01', '2026-11-02', '2026-11-05'],
                ['Beras Uji', 'kg', 14000, 15000, 16000],
            ]),
        ])->assertRedirect();

        $this->assertDatabaseCount('sekarkijang_region_prices', 6);
        $this->assertDatabaseHas('sekarkijang_region_prices', [
            'region_code' => '3511',
            'region_name' => 'Kabupaten Bondowoso',
            'commodity_name' => 'Beras Uji',
            'price' => 12000,
            'price_date' => '2026-11-01',
        ]);
        $this->assertDatabaseHas('sekarkijang_region_prices', [
            'region_code' => '3509',
            'region_name' => 'Kabupaten Jember',
            'commodity_name' => 'Beras Uji',
            'price' => 14000,
            'price_date' => '2026-11-01',
        ]);

        $this->get('/?tab=sekarkijang&start_date=2026-11-01&end_date=2026-11-05&sekarkijang_commodity=Beras%20Uji')
            ->assertOk()
            ->assertSee('labels: ["1 Nov","2 Nov","5 Nov"]', false)
            ->assertSee('"label":"Kabupaten Bondowoso","data":[12000,13000,14000]', false)
            ->assertSee('"label":"Kabupaten Jember","data":[14000,15000,16000]', false)
            ->assertSee('Tambah Excel / Upload Data Kabupaten-Kota')
            ->assertDontSee('dummy');
    }

    public function test_region_excel_upload_rejects_an_invalid_sp2kp_format(): void
    {
        $this->from('/?tab=sekarkijang')
            ->post(route('sekarkijang-prices.upload'), [
                'region_code' => '3511',
                'excel_file' => $this->makeExcelUpload([
                    ['Kolom Tidak Valid'],
                    ['Bukan data harga'],
                ]),
            ])
            ->assertRedirect('/?tab=sekarkijang')
            ->assertSessionHasErrors('excel_file');

        $this->assertDatabaseCount('sekarkijang_region_prices', 0);
    }

    public function test_situbondo_chart_uses_sp2kp_prices_before_the_pasarpanji_fallback(): void
    {
        DB::table('harga_pasar')->insert([
            'nama_pasar' => 'Pasar Rakyat Panji',
            'nama_barang' => 'Beras SP2KP',
            'harga' => 10000,
            'tanggal_dibuat' => '2026-11-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('monitoring_sp2kp_prices')->insert([
            'market_name' => 'Situbondo',
            'commodity_name' => 'Beras SP2KP',
            'unit' => 'kg',
            'price' => 13500,
            'price_date' => '2026-11-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/?tab=sekarkijang&start_date=2026-11-01&end_date=2026-11-01&sekarkijang_commodity=Beras%20SP2KP')
            ->assertOk()
            ->assertSee('"label":"Kabupaten Situbondo (SP2KP)","data":[13500]', false)
            ->assertDontSee('"label":"Kabupaten Situbondo (Pasar Panji)"', false);
    }

    public function test_latest_uploaded_region_data_is_selected_on_a_fresh_dashboard_visit(): void
    {
        DB::table('sekarkijang_region_prices')->insert([
            [
                'region_code' => '3510',
                'region_name' => 'Kabupaten Banyuwangi',
                'market_name' => 'Kabupaten Banyuwangi',
                'commodity_name' => 'Komoditas Tersimpan',
                'unit' => 'kg',
                'price' => 14500,
                'price_date' => '2026-11-03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region_code' => '3510',
                'region_name' => 'Kabupaten Banyuwangi',
                'market_name' => 'Kabupaten Banyuwangi',
                'commodity_name' => 'Komoditas Tersimpan',
                'unit' => 'kg',
                'price' => 15000,
                'price_date' => '2026-11-05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get('/?tab=sekarkijang')
            ->assertOk()
            ->assertSee('<option value="Komoditas Tersimpan" selected>', false)
            ->assertSee('id="sekarkijangStartDate" type="date" name="start_date" value="2026-11-03"', false)
            ->assertSee('id="sekarkijangEndDate" type="date" name="end_date" value="2026-11-05"', false)
            ->assertSee('"label":"Kabupaten Banyuwangi","data":[14500,15000]', false);
    }

    public function test_selected_regional_chart_is_restored_after_navigating_away_and_back(): void
    {
        DB::table('sekarkijang_region_prices')->insert([
            [
                'region_code' => '3511',
                'region_name' => 'Kabupaten Bondowoso',
                'market_name' => 'Kabupaten Bondowoso',
                'commodity_name' => 'Komoditas Dipilih',
                'unit' => 'kg',
                'price' => 12500,
                'price_date' => '2026-11-03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region_code' => '3511',
                'region_name' => 'Kabupaten Bondowoso',
                'market_name' => 'Kabupaten Bondowoso',
                'commodity_name' => 'Komoditas Dipilih',
                'unit' => 'kg',
                'price' => 13000,
                'price_date' => '2026-11-05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region_code' => '3510',
                'region_name' => 'Kabupaten Banyuwangi',
                'market_name' => 'Kabupaten Banyuwangi',
                'commodity_name' => 'Komoditas Unggahan Terbaru',
                'unit' => 'kg',
                'price' => 14500,
                'price_date' => '2026-11-07',
                'created_at' => now(),
                'updated_at' => now()->addMinute(),
            ],
        ]);

        $this->get('/?tab=sekarkijang&start_date=2026-11-03&end_date=2026-11-05&sekarkijang_commodity=Komoditas%20Dipilih')
            ->assertOk()
            ->assertSee('"label":"Kabupaten Bondowoso","data":[12500,13000]', false);

        $this->get('/?tab=beranda')->assertOk();

        $this->get('/?tab=sekarkijang')
            ->assertOk()
            ->assertSee('<option value="Komoditas Dipilih" selected>', false)
            ->assertSee('id="sekarkijangStartDate" type="date" name="start_date" value="2026-11-03"', false)
            ->assertSee('id="sekarkijangEndDate" type="date" name="end_date" value="2026-11-05"', false)
            ->assertSee('"label":"Kabupaten Bondowoso","data":[12500,13000]', false);
    }

    private function makeExcelUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'sekarkijang-prices');

        try {
            (new Xlsx($spreadsheet))->save($temporaryPath);

            return UploadedFile::fake()->createWithContent(
                'sp2kp.xlsx',
                file_get_contents($temporaryPath)
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($temporaryPath);
        }
    }
}
