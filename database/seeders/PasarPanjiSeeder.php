<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PasarPanjiSeeder extends Seeder
{
    public function run()
    {
        $filePath = database_path('seeders/data-pasar.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("File data-pasar.xlsx tidak ditemukan di database/seeders!");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $insertData = [];

        // Skip baris 1 (Header: ID, Nama Pasar, Nama Barang, Harga, Tanggal Dibuat)
        foreach (array_slice($rows, 1) as $row) {
            if (empty($row['B']) || empty($row['C'])) continue;

            $insertData[] = [
                'excel_id'       => $row['A'],
                'nama_pasar'     => trim($row['B']),
                'nama_barang'    => trim($row['C']),
                'harga'          => (float) $row['D'],
                'tanggal_dibuat' => date('Y-m-d', strtotime($row['E'])),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        // Insert sekaligus per 200 baris agar cepat & efisien
        foreach (array_chunk($insertData, 200) as $chunk) {
            DB::table('harga_pasar')->insert($chunk);
        }

        $totalInserted = count($insertData);
        $this->command->info("Berhasil mengimpor {$totalInserted} baris data Pasar Panji ke MySQL!");
    }
}