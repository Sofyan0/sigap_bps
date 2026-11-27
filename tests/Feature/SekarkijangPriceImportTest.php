<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            ->assertSee('Ambil Data Semua Wilayah SP2KP')
            ->assertDontSee('Upload Excel Wilayah Lain');
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

    public function test_probolinggo_county_excel_upload_is_rejected_in_favor_of_direct_sp2kp_fetch(): void
    {
        $this->from('/?tab=sekarkijang')
            ->post(route('sekarkijang-prices.upload'), [
                'region_code' => '3513',
                'excel_file' => $this->makeExcelUpload([
                    ['Komoditas', '2026-11-26'],
                    ['Beras Uji', 14000],
                ]),
            ])
            ->assertRedirect('/?tab=sekarkijang')
            ->assertSessionHasErrors('region_code');

        $this->assertDatabaseCount('sekarkijang_region_prices', 0);
    }

    public function test_all_six_regions_are_fetched_in_one_action_and_situbondo_reuses_monitoring_prices(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            'market_name' => 'Situbondo',
            'commodity_name' => 'Beras Probolinggo Test',
            'unit' => 'kg',
            'price' => 13500,
            'price_date' => '2026-11-26',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $regionPrices = [
            '3513' => ['name' => 'Kabupaten Probolinggo', 'price' => 14000, 'level' => '2', 'market_id' => ''],
            '3574' => ['name' => 'Kota Probolinggo', 'price' => 14500, 'level' => '3', 'market_id' => '460'],
            '3511' => ['name' => 'Kabupaten Bondowoso', 'price' => 15000, 'level' => '2', 'market_id' => ''],
            '3510' => ['name' => 'Kabupaten Banyuwangi', 'price' => 15500, 'level' => '2', 'market_id' => ''],
            '3509' => ['name' => 'Kabupaten Jember', 'price' => 16000, 'level' => '2', 'market_id' => ''],
            '3508' => ['name' => 'Kabupaten Lumajang', 'price' => 16500, 'level' => '2', 'market_id' => ''],
        ];
        Http::fake(function ($request) use ($regionPrices) {
            $fields = collect($request->data())->keyBy('name');
            $regionCode = $fields->get('kode_kab_kota')['contents'] ?? '';
            $price = $regionPrices[$regionCode]['price'] ?? 0;

            return Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'variant' => 'Beras Probolinggo Test',
                        'satuan' => 'kg',
                        'daftarHarga' => [
                            ['date' => '2026-11-26', 'harga' => $price],
                        ],
                    ],
                ],
            ]);
        });

        $this->followingRedirects()
            ->post(route('sekarkijang-prices.fetch-all'), [
                'start_date' => '2026-11-26',
                'end_date' => '2026-11-26',
                'sekarkijang_commodity' => 'Beras Probolinggo Test',
            ])
            ->assertOk()
            ->assertSee('"label":"Kabupaten Situbondo (SP2KP)","data":[13500]', false)
            ->assertSee('"label":"Kabupaten Probolinggo","data":[14000]', false)
            ->assertSee('"label":"Kota Probolinggo","data":[14500]', false)
            ->assertSee('"label":"Kabupaten Bondowoso","data":[15000]', false)
            ->assertSee('"label":"Kabupaten Banyuwangi","data":[15500]', false)
            ->assertSee('"label":"Kabupaten Jember","data":[16000]', false)
            ->assertSee('"label":"Kabupaten Lumajang","data":[16500]', false)
            ->assertSee('6 data harga SP2KP berhasil diambil dari 6 wilayah.');

        Http::assertSentCount(6);
        $actualRegionParameters = Http::recorded()->map(function ($pair) {
            $request = $pair[0];
            $fields = collect($request->data())->keyBy('name');

            return [
                'region_code' => $fields->get('kode_kab_kota')['contents'] ?? null,
                'url' => $request->url(),
                'method' => $request->method(),
                'level' => $fields->get('level')['contents'] ?? null,
                'market_id' => $fields->get('pasar_id')['contents'] ?? null,
            ];
        })->keyBy('region_code')->all();
        $expectedRegionParameters = collect($regionPrices)->map(fn ($region, $regionCode) => [
            'region_code' => (string) $regionCode,
            'url' => 'https://api-sp2kp.kemendag.go.id/report/api/average-price/export-area-daily-json',
            'method' => 'POST',
            'level' => $region['level'],
            'market_id' => $region['market_id'],
        ])->keyBy('region_code')->all();
        $this->assertSame($expectedRegionParameters, $actualRegionParameters);

        foreach ($regionPrices as $regionCode => $region) {
            $this->assertDatabaseHas('sekarkijang_region_prices', [
                'region_code' => $regionCode,
                'region_name' => $region['name'],
                'market_name' => $region['name'],
                'commodity_name' => 'Beras Probolinggo Test',
                'price' => $region['price'],
                'price_date' => '2026-11-26',
            ]);
        }
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
