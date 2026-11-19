<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createPriceTable('monitoring_sikepo_prices', 'monitoring_sikepo_row_unique');
        $this->createPriceTable('monitoring_sp2kp_prices', 'monitoring_sp2kp_row_unique');

        if (! Schema::hasTable('monitoring_harga_prices')) {
            return;
        }

        foreach ([
            'sikeppo' => 'monitoring_sikepo_prices',
            'sp2kp' => 'monitoring_sp2kp_prices',
        ] as $source => $targetTable) {
            DB::table('monitoring_harga_prices')
                ->where('source', $source)
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($targetTable) {
                    $records = $rows->map(fn ($row) => [
                        'market_name' => $row->market_name,
                        'commodity_name' => $row->commodity_name,
                        'unit' => $row->unit,
                        'price' => $row->price,
                        'price_date' => $row->price_date,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ])->all();

                    DB::table($targetTable)->upsert(
                        $records,
                        ['market_name', 'commodity_name', 'price_date'],
                        ['unit', 'price', 'updated_at']
                    );
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('monitoring_harga_prices')) {
            foreach ([
                'monitoring_sikepo_prices' => 'sikeppo',
                'monitoring_sp2kp_prices' => 'sp2kp',
            ] as $sourceTable => $source) {
                if (! Schema::hasTable($sourceTable)) {
                    continue;
                }

                DB::table($sourceTable)
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($source) {
                        $records = $rows->map(fn ($row) => [
                            'source' => $source,
                            'market_name' => $row->market_name,
                            'commodity_name' => $row->commodity_name,
                            'unit' => $row->unit,
                            'price' => $row->price,
                            'price_date' => $row->price_date,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ])->all();

                        DB::table('monitoring_harga_prices')->upsert(
                            $records,
                            ['source', 'market_name', 'commodity_name', 'price_date'],
                            ['unit', 'price', 'updated_at']
                        );
                    });
            }
        }

        Schema::dropIfExists('monitoring_sikepo_prices');
        Schema::dropIfExists('monitoring_sp2kp_prices');
    }

    private function createPriceTable(string $tableName, string $uniqueIndexName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($uniqueIndexName) {
            $table->id();
            $table->string('market_name')->default('');
            $table->string('commodity_name');
            $table->string('unit', 30)->default('');
            $table->decimal('price', 14, 2);
            $table->date('price_date');
            $table->timestamps();

            $table->unique(['market_name', 'commodity_name', 'price_date'], $uniqueIndexName);
            $table->index('price_date');
        });
    }
};
