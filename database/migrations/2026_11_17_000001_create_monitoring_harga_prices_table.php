<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_harga_prices', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('market_name')->default('');
            $table->string('commodity_name');
            $table->string('unit', 30)->default('');
            $table->decimal('price', 14, 2);
            $table->date('price_date');
            $table->timestamps();

            $table->unique(['source', 'market_name', 'commodity_name', 'price_date'], 'monitoring_harga_source_row_unique');
            $table->index(['source', 'price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_harga_prices');
    }
};
