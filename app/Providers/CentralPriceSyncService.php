<?php

namespace App\Providers;

use App\Models\Commodity;
use App\Models\PriceRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CentralPriceSyncService
{
    public function sync(): array
    {
        $url = config('sigap.central_api.url');
        $key = config('sigap.central_api.key');
        $timeout = config('sigap.central_api.timeout', 10);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $key,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->failed()) {
                Log::error('SIGAP Central Sync Failed: ' . $response->body());
                return ['success' => false, 'message' => 'Gagal mengambil data dari API pusat.'];
            }

            $items = $response->json('data', []);
            $syncedCount = 0;

            foreach ($items as $item) {
                $commodity = Commodity::firstOrCreate(
                    ['code' => $item['commodity_code']],
                    [
                        'name' => $item['commodity_name'],
                        'unit' => $item['unit'] ?? 'kg',
                        'category' => $item['category'] ?? 'Sembako',
                    ]
                );

                PriceRecord::updateOrCreate(
                    [
                        'commodity_id' => $commodity->id,
                        'recorded_at' => $item['date'],
                        'market_name' => $item['market_name'] ?? 'Pasar Utama',
                    ],
                    [
                        'price' => $item['price'],
                        'source' => 'central',
                    ]
                );

                $syncedCount++;
            }

            return ['success' => true, 'synced' => $syncedCount];
        } catch (\Exception $e) {
            Log::error('SIGAP Central Sync Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}