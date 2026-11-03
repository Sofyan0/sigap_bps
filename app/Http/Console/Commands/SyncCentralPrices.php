<?php

namespace App\Http\Console\Commands;

use App\Providers\CentralPriceSyncService;
use Illuminate\Console\Command;

class SyncCentralPrices extends Command
{
    protected $signature = 'sigap:sync-prices';
    protected $description = 'Sinkronisasi data harga pangan dari API Pusat SIGAP Situbondo';

    public function handle(CentralPriceSyncService $syncService): int
    {
        $this->info('Memulai sinkronisasi data harga SIGAP...');

        $result = $syncService->sync();

        if ($result['success']) {
            $this->info("Berhasil menyinkronkan {$result['synced']} data harga.");
            return Command::SUCCESS;
        }

        $this->error("Sinkronisasi gagal: " . ($result['message'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}