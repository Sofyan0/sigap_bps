<?php

namespace Database\Seeders;

use App\Models\EwsAssessment;
use App\Models\EwsCommodity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EwsAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $commodities = [
            ['code' => 'CBI-RAWIT-MERAH', 'name' => 'Cabe Rawit Merah', 'unit' => 'kg', 'reference_price' => 57000, 'price' => 62000, 'cv' => 22.4, 'risk' => 87, 'status' => 'Intervensi', 'stock' => 12.4, 'stock_unit' => 'Ton', 'demand' => 28, 'demand_unit' => 'Ton', 'days' => 13],
            ['code' => 'BWM-SUPER', 'name' => 'Bawang Merah (Super)', 'unit' => 'kg', 'reference_price' => 22000, 'price' => 24000, 'cv' => 18.1, 'risk' => 73, 'status' => 'Intervensi', 'stock' => 32, 'stock_unit' => 'Ton', 'demand' => 45, 'demand_unit' => 'Ton', 'days' => 21],
            ['code' => 'CBM-BESAR', 'name' => 'Cabe Merah Besar', 'unit' => 'kg', 'reference_price' => 25000, 'price' => 30000, 'cv' => 14.8, 'risk' => 62, 'status' => 'Waspada', 'stock' => 18.5, 'stock_unit' => 'Ton', 'demand' => 22, 'demand_unit' => 'Ton', 'days' => 25],
            ['code' => 'TLR-AYAM-RAS', 'name' => 'Telur Ayam Ras', 'unit' => 'kg', 'reference_price' => 27000, 'price' => 25000, 'cv' => 3.8, 'risk' => 48, 'status' => 'Aman', 'stock' => 58, 'stock_unit' => 'Ton', 'demand' => 60, 'demand_unit' => 'Ton', 'days' => 29],
            ['code' => 'DGN-AYAM-RAS', 'name' => 'Daging Ayam Ras', 'unit' => 'kg', 'reference_price' => 36750, 'price' => 36000, 'cv' => 5.4, 'risk' => 35, 'status' => 'Aman', 'stock' => 75, 'stock_unit' => 'Ton', 'demand' => 65, 'demand_unit' => 'Ton', 'days' => 34],
            ['code' => 'BRS-PREMIUM', 'name' => 'Beras Premium (TH 55)', 'unit' => 'kg', 'reference_price' => 14900, 'price' => 15000, 'cv' => 1.2, 'risk' => 31, 'status' => 'Aman', 'stock' => 850, 'stock_unit' => 'Ton', 'demand' => 600, 'demand_unit' => 'Ton', 'days' => 42],
            ['code' => 'MYK-CURAH', 'name' => 'Minyak Goreng Curah', 'unit' => 'L', 'reference_price' => 15700, 'price' => 16500, 'cv' => 1.5, 'risk' => 28, 'status' => 'Aman', 'stock' => 62000, 'stock_unit' => 'L', 'demand' => 38000, 'demand_unit' => 'L', 'days' => 48],
            ['code' => 'GLA-KRISTAL-PUTIH', 'name' => 'Gula Kristal Putih', 'unit' => 'kg', 'reference_price' => 17500, 'price' => 17000, 'cv' => 2.0, 'risk' => 22, 'status' => 'Aman', 'stock' => 120, 'stock_unit' => 'Ton', 'demand' => 90, 'demand_unit' => 'Ton', 'days' => 40],
            ['code' => 'BRS-MEDIUM-BULOG', 'name' => 'Beras Medium (Bulog)', 'unit' => 'kg', 'reference_price' => 12500, 'price' => 12500, 'cv' => 2.1, 'risk' => 18, 'status' => 'Aman', 'stock' => 1250, 'stock_unit' => 'Ton', 'demand' => 820, 'demand_unit' => 'Ton', 'days' => 45],
            ['code' => 'BWP-SINCO-HONAN', 'name' => 'Bawang Putih Sinco/Honan', 'unit' => 'kg', 'reference_price' => 32000, 'price' => 34000, 'cv' => 4.2, 'risk' => 28, 'status' => 'Aman', 'stock' => 45, 'stock_unit' => 'Ton', 'demand' => 35, 'demand_unit' => 'Ton', 'days' => 38],
            ['code' => 'DGN-SAPI-PAHA', 'name' => 'Daging Sapi Paha Belakang', 'unit' => 'kg', 'reference_price' => 130000, 'price' => 120000, 'cv' => 1.8, 'risk' => 16, 'status' => 'Aman', 'stock' => 25, 'stock_unit' => 'Ton', 'demand' => 18, 'demand_unit' => 'Ton', 'days' => 41],
        ];

        DB::transaction(function () use ($commodities): void {
            $assessment = EwsAssessment::updateOrCreate(
                [
                    'observed_on' => today()->toDateString(),
                    'market_name' => 'Pasar Panji',
                ],
                [
                    'overall_risk_score' => 82,
                    'overall_status' => 'Waspada',
                ]
            );

            foreach ($commodities as $item) {
                $commodity = EwsCommodity::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'unit' => $item['unit'],
                        'reference_price' => $item['reference_price'],
                        'is_active' => true,
                    ]
                );

                $assessment->metrics()->updateOrCreate(
                    ['ews_commodity_id' => $commodity->id],
                    [
                        'price' => $item['price'],
                        'volatility_cv' => $item['cv'],
                        'risk_score' => $item['risk'],
                        'alert_status' => $item['status'],
                        'stock_quantity' => $item['stock'],
                        'stock_unit' => $item['stock_unit'],
                        'demand_quantity' => $item['demand'],
                        'demand_unit' => $item['demand_unit'],
                        'coverage_days' => $item['days'],
                    ]
                );
            }
        });
    }
}
