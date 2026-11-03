<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\PriceRecord;
use Illuminate\Database\Seeder;

class SigapDemoSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'BRG-01', 'name' => 'Beras Medium', 'category' => 'Beras', 'unit' => 'kg', 'price' => 12500],
            ['code' => 'BRG-02', 'name' => 'Beras Premium', 'category' => 'Beras', 'unit' => 'kg', 'price' => 15000],
            ['code' => 'CLG-01', 'name' => 'Cabai Merah Besar', 'category' => 'Hortikultura', 'unit' => 'kg', 'price' => 32000],
            ['code' => 'CLG-02', 'name' => 'Cabai Rawit Merah', 'category' => 'Hortikultura', 'unit' => 'kg', 'price' => 45000],
            ['code' => 'BWM-01', 'name' => 'Bawang Merah', 'category' => 'Hortikultura', 'unit' => 'kg', 'price' => 28000],
            ['code' => 'BWP-01', 'name' => 'Bawang Putih Honan', 'category' => 'Hortikultura', 'unit' => 'kg', 'price' => 36000],
            ['code' => 'MNG-01', 'name' => 'Minyak Goreng Kemasan', 'category' => 'Minyak', 'unit' => 'liter', 'price' => 18000],
            ['code' => 'GUR-01', 'name' => 'Gula Pasir', 'category' => 'Gula', 'unit' => 'kg', 'price' => 17000],
        ];

        $today = date('Y-m-d');

        foreach ($items as $item) {
            $commodity = Commodity::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'unit' => $item['unit'],
                ]
            );

            PriceRecord::updateOrCreate(
                [
                    'commodity_id' => $commodity->id,
                    'recorded_at' => $today,
                    'market_name' => 'Pasar Mimbaan Situbondo',
                ],
                [
                    'price' => $item['price'],
                    'source' => 'local',
                ]
            );
        }
    }
}