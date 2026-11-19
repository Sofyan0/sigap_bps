<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_commodities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('unit', 20)->default('kg');
            $table->decimal('reference_price', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ews_assessments', function (Blueprint $table) {
            $table->id();
            $table->date('observed_on');
            $table->string('market_name')->default('Pasar Panji');
            $table->unsignedTinyInteger('overall_risk_score');
            $table->string('overall_status', 30);
            $table->timestamps();

            $table->unique(['observed_on', 'market_name']);
        });

        Schema::create('ews_commodity_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ews_assessment_id')->constrained('ews_assessments')->cascadeOnDelete();
            $table->foreignId('ews_commodity_id')->constrained('ews_commodities')->restrictOnDelete();
            $table->decimal('price', 14, 2);
            $table->decimal('volatility_cv', 7, 2);
            $table->unsignedTinyInteger('risk_score');
            $table->string('alert_status', 30);
            $table->decimal('stock_quantity', 14, 3)->nullable();
            $table->string('stock_unit', 20)->nullable();
            $table->decimal('demand_quantity', 14, 3)->nullable();
            $table->string('demand_unit', 20)->nullable();
            $table->unsignedSmallInteger('coverage_days')->nullable();
            $table->timestamps();

            $table->unique(['ews_assessment_id', 'ews_commodity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_commodity_metrics');
        Schema::dropIfExists('ews_assessments');
        Schema::dropIfExists('ews_commodities');
    }
};
