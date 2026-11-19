<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisForecast extends Model
{
    protected $fillable = [
        'commodity_name',
        'forecast_date',
        'predicted_price',
        'lower_bound',
        'upper_bound',
    ];

    protected function casts(): array
    {
        return [
            'forecast_date' => 'date',
            'predicted_price' => 'decimal:2',
            'lower_bound' => 'decimal:2',
            'upper_bound' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, 'analysis_run_id');
    }
}
