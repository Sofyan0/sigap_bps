<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisRun extends Model
{
    protected $fillable = [
        'model_name',
        'model_accuracy',
        'training_data_start',
        'training_data_end',
        'forecast_horizon_days',
        'projected_peak_date',
        'expected_inflation_percent',
        'main_factor',
        'factor_correlation_percent',
        'insight',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'model_accuracy' => 'decimal:2',
            'training_data_start' => 'date',
            'training_data_end' => 'date',
            'forecast_horizon_days' => 'integer',
            'projected_peak_date' => 'date',
            'expected_inflation_percent' => 'decimal:3',
            'factor_correlation_percent' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(AnalysisForecast::class);
    }
}
