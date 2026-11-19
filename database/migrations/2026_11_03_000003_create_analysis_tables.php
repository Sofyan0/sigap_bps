<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->decimal('model_accuracy', 5, 2)->nullable();
            $table->date('training_data_start')->nullable();
            $table->date('training_data_end')->nullable();
            $table->unsignedSmallInteger('forecast_horizon_days')->nullable();
            $table->date('projected_peak_date')->nullable();
            $table->decimal('expected_inflation_percent', 7, 3)->nullable();
            $table->string('main_factor')->nullable();
            $table->decimal('factor_correlation_percent', 5, 2)->nullable();
            $table->text('insight')->nullable();
            $table->timestamp('generated_at')->index();
            $table->timestamps();
        });

        Schema::create('analysis_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->string('commodity_name');
            $table->date('forecast_date');
            $table->decimal('predicted_price', 14, 2);
            $table->decimal('lower_bound', 14, 2)->nullable();
            $table->decimal('upper_bound', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['analysis_run_id', 'commodity_name', 'forecast_date'], 'analysis_forecasts_run_commodity_date_unique');
            $table->index(['commodity_name', 'forecast_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_forecasts');
        Schema::dropIfExists('analysis_runs');
    }
};
