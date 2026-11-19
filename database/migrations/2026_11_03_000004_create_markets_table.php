<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('district', 100);
            $table->string('region', 100)->default('Situbondo');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('monitoring_status', 30)->default('Stabil');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['region', 'is_active', 'monitoring_status'], 'markets_region_active_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
