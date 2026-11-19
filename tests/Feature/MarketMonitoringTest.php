<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_monitoring_page_reads_market_details_and_status_counts_from_database(): void
    {
        DB::table('markets')->insert([
            [
                'code' => 'TEST-PANJI',
                'name' => 'Pasar Uji Panji',
                'district' => 'Panji',
                'region' => 'Situbondo',
                'latitude' => -7.7334240,
                'longitude' => 113.6922420,
                'location_verified' => true,
                'location_address' => 'Besuki, Situbondo, Jawa Timur',
                'location_source_url' => 'https://www.openstreetmap.org/node/13986031602',
                'monitoring_status' => 'Intervensi',
                'is_active' => true,
            ],
            [
                'code' => 'TEST-BESUKI',
                'name' => 'Pasar Uji Besuki',
                'district' => 'Besuki',
                'region' => 'Situbondo',
                'latitude' => -7.7348,
                'longitude' => 113.6931,
                'location_verified' => false,
                'location_address' => null,
                'location_source_url' => null,
                'monitoring_status' => 'Waspada',
                'is_active' => true,
            ],
            [
                'code' => 'TEST-INACTIVE',
                'name' => 'Pasar Nonaktif',
                'district' => 'Panji',
                'region' => 'Situbondo',
                'latitude' => -7.7000,
                'longitude' => 113.9900,
                'location_verified' => false,
                'location_address' => null,
                'location_source_url' => null,
                'monitoring_status' => 'Stabil',
                'is_active' => false,
            ],
        ]);

        $response = $this->get('/?tab=monitoring_pasar');

        $response->assertOk()
            ->assertSee('2 Pasar Rakyat')
            ->assertSee('1 Pasar')
            ->assertSee('Pasar Uji Panji')
            ->assertSee('Pasar Uji Besuki')
            ->assertSee('Besuki, Situbondo, Jawa Timur')
            ->assertSee('Sumber lokasi: OpenStreetMap')
            ->assertSee('Lokasi belum terverifikasi; pin tidak ditampilkan.')
            ->assertSee('"lat":-7.733424', false)
            ->assertDontSee('"lat":-7.7348', false)
            ->assertDontSee('Pasar Nonaktif');
    }

    public function test_market_monitoring_page_shows_empty_state_when_no_active_markets_exist(): void
    {
        $response = $this->get('/?tab=monitoring_pasar');

        $response->assertOk()
            ->assertSee('0 Pasar Rakyat')
            ->assertSee('Belum ada data pasar aktif di database.');
    }

    public function test_market_seeder_only_marks_openstreetmap_verified_markets_for_mapping(): void
    {
        $this->seed(\Database\Seeders\MarketSeeder::class);

        $verifiedMarkets = DB::table('markets')
        ->where('location_verified', true)
        ->orderBy('code')
        ->get();

        $this->assertCount(4, $verifiedMarkets);
        $this->assertSame(-7.733424, (float) $verifiedMarkets->firstWhere('code', 'SIT-BESUKI')->latitude);
        $this->assertSame(113.9411047, (float) $verifiedMarkets->firstWhere('code', 'SIT-PANARUKAN')->longitude);
        $this->assertSame(-7.7490374, (float) $verifiedMarkets->firstWhere('code', 'SIT-ASEMBAGUS')->latitude);
        $this->assertSame(-7.7058543, (float) $verifiedMarkets->firstWhere('code', 'SIT-PANJI')->latitude);
        $this->assertSame(114.0110516, (float) $verifiedMarkets->firstWhere('code', 'SIT-PANJI')->longitude);
        $this->assertTrue((bool) DB::table('markets')
        ->where('code', 'SIT-PANJI')
        ->value('location_verified'));
    }
}
