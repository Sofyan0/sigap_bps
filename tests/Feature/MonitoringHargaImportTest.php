<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MonitoringHargaImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sp2kp_excel_upload_is_saved_and_rendered_without_replacing_sikepo_data(): void
    {
        DB::table('monitoring_sikepo_prices')->insert([
            'market_name' => 'Pasar Rakyat Panji',
            'commodity_name' => 'Data SIKEPO yang tetap ada',
            'unit' => 'kg',
            'price' => 10000,
            'price_date' => '2026-11-01',
        ]);

        $file = $this->makeExcelUpload([
            ['komoditas', 'satuan', '2026-11-01', '2026-11-02'],
            ['Komoditas Uji SP2KP', 'kg', 12500, 13500],
        ]);

        $response = $this->post(route('monitoring-harga.upload'), [
            'source' => 'sp2kp',
            'excel_file' => $file,
        ])->assertRedirect();

        $this->assertDatabaseCount('monitoring_sp2kp_prices', 2);
        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Komoditas Uji SP2KP',
            'price' => 12500,
            'price_date' => '2026-11-01',
        ]);
        $this->assertDatabaseHas('monitoring_sikepo_prices', [
            'commodity_name' => 'Data SIKEPO yang tetap ada',
        ]);

        $this->followingRedirects()
            ->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Menampilkan data hasil upload Excel yang tersimpan di database SP2KP')
            ->assertSee('Komoditas Uji SP2KP')
            ->assertSee('Rp 13.500')
            ->assertSee('"label":"Komoditas Uji SP2KP (SP2KP)","data":[12500,13500]', false)
            ->assertDontSee('Data SIKEPO yang tetap ada');
    }

    public function test_sikepo_csv_upload_is_saved_and_displayed_in_its_own_source(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'sikepo.csv',
            "excel_id,nama_pasar,nama_barang,harga,tanggal_dibuat,satuan\n1,Pasar Rakyat Panji,Komoditas Uji SIKEPO,25.000,2026-11-03,kg\n"
        );

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sikeppo',
                'excel_file' => $file,
            ])
            ->assertOk()
            ->assertSee('Menampilkan data hasil upload Excel yang tersimpan di database SIKEPO')
            ->assertSee('Komoditas Uji SIKEPO')
            ->assertSee('Rp 25.000')
            ->assertSee('"label":"Komoditas Uji SIKEPO (SIKEPO)","data":[25000]', false);

        $this->assertDatabaseHas('monitoring_sikepo_prices', [
            'market_name' => 'Pasar Rakyat Panji',
            'commodity_name' => 'Komoditas Uji SIKEPO',
            'price' => 25000,
            'price_date' => '2026-11-03',
        ]);
    }

    public function test_new_upload_keeps_older_dates_and_only_charts_dates_with_prices(): void
    {
        $firstFile = $this->makeExcelUpload([
            ['komoditas', 'harga', 'tanggal'],
            ['Komoditas Berlanjut', 10000, '2026-11-01'],
            ['Komoditas Berlanjut', 12000, '2026-11-03'],
        ]);

        $this->post(route('monitoring-harga.upload'), [
            'source' => 'sikeppo',
            'excel_file' => $firstFile,
        ])->assertRedirect();

        $secondFile = $this->makeExcelUpload([
            ['komoditas', 'harga', 'tanggal'],
            ['Komoditas Berlanjut', 13000, '2026-11-03'],
            ['Komoditas Berlanjut', 14000, '2026-12-02'],
        ]);

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sikeppo',
                'excel_file' => $secondFile,
            ])
            ->assertOk()
            ->assertSee('"label":"Komoditas Berlanjut (SIKEPO)","data":[10000,13000,14000]', false);

        $this->assertDatabaseCount('monitoring_sikepo_prices', 3);
        $this->assertDatabaseHas('monitoring_sikepo_prices', [
            'commodity_name' => 'Komoditas Berlanjut',
            'price' => 10000,
            'price_date' => '2026-11-01',
        ]);
        $this->assertDatabaseHas('monitoring_sikepo_prices', [
            'commodity_name' => 'Komoditas Berlanjut',
            'price' => 13000,
            'price_date' => '2026-11-03',
        ]);
    }

    public function test_sp2kp_upload_without_unit_column_is_saved_with_empty_unit(): void
    {
        $file = $this->makeExcelUpload([
            ['komoditas', '2026-11-06'],
            ['Komoditas Tanpa Satuan', 15000],
        ]);

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sp2kp',
                'excel_file' => $file,
            ])
            ->assertOk()
            ->assertSee('Komoditas Tanpa Satuan');

        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Komoditas Tanpa Satuan',
            'unit' => '',
            'price' => 15000,
            'price_date' => '2026-11-06',
        ]);
    }

    public function test_sp2kp_finds_header_after_title_row_and_accepts_common_column_names(): void
    {
        $file = $this->makeExcelUpload([
            ['Laporan Harga Pangan Kabupaten Situbondo'],
            ['Nama Komoditi', '01/11/2026', '03/11/2026'],
            ['Beras Premium', 14900, 15000],
        ]);

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sp2kp',
                'excel_file' => $file,
            ])
            ->assertOk()
            ->assertSee('Beras Premium')
            ->assertSee('Rp 15.000');

        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Beras Premium',
            'price' => 14900,
            'price_date' => '2026-11-01',
        ]);
        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Beras Premium',
            'price' => 15000,
            'price_date' => '2026-11-03',
        ]);
    }

    public function test_invalid_upload_does_not_remove_existing_source_data(): void
    {
        DB::table('monitoring_sp2kp_prices')->insert([
            'market_name' => 'Situbondo',
            'commodity_name' => 'Data yang sudah ada',
            'unit' => 'kg',
            'price' => 10000,
            'price_date' => '2026-11-01',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'invalid.csv',
            "kolom_tidak_sesuai\nbaris\n"
        );

        $this->from('/?tab=monitoring_harga&source=sp2kp')
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sp2kp',
                'excel_file' => $file,
            ])
            ->assertRedirect('/?tab=monitoring_harga&source=sp2kp')
            ->assertSessionHasErrors('excel_file');

        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Data yang sudah ada',
        ]);
    }

    public function test_sp2kp_variant_report_format_is_uploaded_to_its_own_table(): void
    {
        $file = $this->makeExcelUpload([
            ['LAPORAN HARGA HARIAN Kab. Situbondo, Jawa Timur'],
            ['PERIODE: 2026-11-17 s/d 2026-11-17'],
            [''],
            ['Variant', 'Quantity', 'Unit', '2026-11-17'],
            ['Beras Medium', 1, 'kg', 13250],
            ['Beras Premium', 1, 'kg', 14900],
        ]);

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sp2kp',
                'excel_file' => $file,
            ])
            ->assertOk()
            ->assertSee('Beras Medium')
            ->assertSee('Rp 14.900');

        $this->assertDatabaseCount('monitoring_sp2kp_prices', 2);
        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Beras Medium',
            'unit' => 'kg',
            'price' => 13250,
            'price_date' => '2026-11-17',
        ]);
        $this->assertDatabaseMissing('monitoring_sikepo_prices', [
            'commodity_name' => 'Beras Medium',
        ]);
    }

    public function test_sp2kp_data_is_found_when_active_worksheet_is_not_the_data_sheet(): void
    {
        $file = $this->makeMultiSheetExcelUpload([
            ['Variant', 'Quantity', 'Unit', '2026-11-17'],
            ['Beras Premium', 1, 'kg', 14900],
        ]);

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sp2kp',
                'excel_file' => $file,
            ])
            ->assertOk()
            ->assertSee('Beras Premium')
            ->assertSee('Rp 14.900');

        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Beras Premium',
            'unit' => 'kg',
            'price' => 14900,
            'price_date' => '2026-11-17',
        ]);
    }

    public function test_sp2kp_average_prices_report_uses_period_and_harga_column(): void
    {
        $file = $this->makeMultiSheetExcelUpload([
            ['LAPORAN HARGA HARIAN Kab. Situbondo, Jawa Timur'],
            ['PERIODE: 2026-11-17 s/d 2026-11-17'],
            [''],
            ['Nama Barang', 'Quantity', 'Unit', 'Harga'],
            ['Beras Medium', 1, 'kg', 13250],
            ['Beras Premium', 1, 'kg', 14900],
        ]);

        $this->followingRedirects()
            ->post(route('monitoring-harga.upload'), [
                'source' => 'sp2kp',
                'excel_file' => $file,
            ])
            ->assertOk()
            ->assertSee('Beras Medium')
            ->assertSee('Rp 14.900');

        $this->assertDatabaseCount('monitoring_sp2kp_prices', 2);
        $this->assertDatabaseHas('monitoring_sp2kp_prices', [
            'commodity_name' => 'Beras Medium',
            'unit' => 'kg',
            'price' => 13250,
            'price_date' => '2026-11-17',
        ]);
    }

    private function makeExcelUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'monitoring-harga');

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

    private function makeMultiSheetExcelUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Ringkasan');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Laporan SP2KP');
        $dataSheet = $spreadsheet->createSheet();
        $dataSheet->setTitle('Data Harga');
        $dataSheet->fromArray($rows, null, 'A1');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'monitoring-harga');

        try {
            (new Xlsx($spreadsheet))->save($temporaryPath);

            return UploadedFile::fake()->createWithContent(
                'sp2kp-multi-sheet.xlsx',
                file_get_contents($temporaryPath)
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($temporaryPath);
        }
    }
}
