<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('Umum');
            $table->string('unit')->default('kg');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained('commodities')->onDelete('cascade');
            $table->date('recorded_at');
            $table->string('market_name')->default('Pasar Mimbaan');
            $table->decimal('price', 12, 2);
            $table->string('source')->default('local'); // 'local' atau 'central'
            $table->timestamps();

            $table->unique(['commodity_id', 'recorded_at', 'market_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_records');
        Schema::dropIfExists('commodities');
    }
};