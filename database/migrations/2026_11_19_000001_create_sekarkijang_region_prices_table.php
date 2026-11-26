<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekarkijang_region_prices', function (Blueprint $table) {
            $table->id();
            $table->string('region_code', 4);
            $table->string('region_name');
            $table->string('market_name')->default('');
            $table->string('commodity_name');
            $table->string('unit', 30)->default('');
            $table->decimal('price', 14, 2);
            $table->date('price_date');
            $table->timestamps();

            $table->unique(
                ['region_code', 'market_name', 'commodity_name', 'price_date'],
                'sekarkijang_region_price_unique'
            );
            $table->index(['region_code', 'commodity_name', 'price_date'], 'sekarkijang_region_price_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekarkijang_region_prices');
    }
};
