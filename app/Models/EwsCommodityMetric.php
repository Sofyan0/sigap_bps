<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwsCommodityMetric extends Model
{
    protected $fillable = [
        'ews_assessment_id',
        'ews_commodity_id',
        'price',
        'volatility_cv',
        'risk_score',
        'alert_status',
        'stock_quantity',
        'stock_unit',
        'demand_quantity',
        'demand_unit',
        'coverage_days',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'volatility_cv' => 'decimal:2',
            'risk_score' => 'integer',
            'stock_quantity' => 'decimal:3',
            'demand_quantity' => 'decimal:3',
            'coverage_days' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(EwsAssessment::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(EwsCommodity::class, 'ews_commodity_id');
    }
}
