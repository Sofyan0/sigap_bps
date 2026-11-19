<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            ['code' => 'SIT-PANJI', 'name' => 'Pasar Mimbaan', 'district' => 'Panji', 'status' => 'Intervensi', 'latitude' => -7.7058543, 'longitude' => 114.0110516, 'location_verified' => true, 'location_address' => 'Jalan Sapudi, Situbondo, Jawa Timur', 'location_source_url' => 'https://www.openstreetmap.org/way/240971078'],
            ['code' => 'SIT-BESUKI', 'name' => 'Pasar Rakyat Besuki', 'district' => 'Besuki', 'status' => 'Intervensi', 'latitude' => -7.7334240, 'longitude' => 113.6922420, 'location_verified' => true, 'location_address' => 'Besuki, Situbondo, Jawa Timur', 'location_source_url' => 'https://www.openstreetmap.org/node/13986031602'],
            ['code' => 'SIT-JANGKAR', 'name' => 'Pasar Jangkar', 'district' => 'Jangkar', 'status' => 'Intervensi', 'latitude' => -7.7120, 'longitude' => 114.1500, 'location_verified' => false],
            ['code' => 'SIT-PANARUKAN', 'name' => 'Pasar Panarukan', 'district' => 'Panarukan', 'status' => 'Waspada', 'latitude' => -7.6963087, 'longitude' => 113.9411047, 'location_verified' => true, 'location_address' => 'Kilensari, Situbondo, Jawa Timur', 'location_source_url' => 'https://www.openstreetmap.org/way/328282561'],
            ['code' => 'SIT-ASEMBAGUS', 'name' => 'Pasar Asembagus', 'district' => 'Asembagus', 'status' => 'Waspada', 'latitude' => -7.7490374, 'longitude' => 114.2181617, 'location_verified' => true, 'location_address' => 'Jalan Raya Banyuwangi, Asembagus, Situbondo, Jawa Timur', 'location_source_url' => 'https://www.openstreetmap.org/way/328697375'],
            ['code' => 'SIT-BUNGATAN', 'name' => 'Pasar Bungatan', 'district' => 'Bungatan', 'status' => 'Waspada', 'latitude' => -7.7180, 'longitude' => 113.8200, 'location_verified' => false],
            ['code' => 'SIT-MANGARAN', 'name' => 'Pasar Mangaran', 'district' => 'Mangaran', 'status' => 'Waspada', 'latitude' => -7.6500, 'longitude' => 114.0200, 'location_verified' => false],
            ['code' => 'SIT-MLANDINGAN', 'name' => 'Pasar Mlandingan', 'district' => 'Mlandingan', 'status' => 'Waspada', 'latitude' => -7.7080, 'longitude' => 113.8700, 'location_verified' => false],
            ['code' => 'SIT-KENDIT', 'name' => 'Pasar Kendit', 'district' => 'Kendit', 'status' => 'Stabil', 'latitude' => -7.7100, 'longitude' => 113.9000, 'location_verified' => false],
            ['code' => 'SIT-BANYUGLUGUR', 'name' => 'Pasar Banyuglugur', 'district' => 'Banyuglugur', 'status' => 'Stabil', 'latitude' => -7.7200, 'longitude' => 113.6300, 'location_verified' => false],
            ['code' => 'SIT-SUBOH', 'name' => 'Pasar Suboh', 'district' => 'Suboh', 'status' => 'Stabil', 'latitude' => -7.7100, 'longitude' => 113.7500, 'location_verified' => false],
            ['code' => 'SIT-SUMBERMALANG', 'name' => 'Pasar Sumbermalang', 'district' => 'Sumbermalang', 'status' => 'Stabil', 'latitude' => -7.8000, 'longitude' => 113.7300, 'location_verified' => false],
        ];

        foreach ($markets as $market) {
            DB::table('markets')->updateOrInsert(
                ['code' => $market['code']],
                [
                    'name' => $market['name'],
                    'district' => $market['district'],
                    'region' => 'Situbondo',
                    'latitude' => $market['latitude'],
                    'longitude' => $market['longitude'],
                    'location_verified' => $market['location_verified'],
                    'location_address' => $market['location_address'] ?? null,
                    'location_source_url' => $market['location_source_url'] ?? null,
                    'monitoring_status' => $market['status'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
