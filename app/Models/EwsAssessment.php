<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EwsAssessment extends Model
{
    protected $fillable = [
        'observed_on',
        'market_name',
        'overall_risk_score',
        'overall_status',
    ];

    protected function casts(): array
    {
        return [
            'observed_on' => 'date',
            'overall_risk_score' => 'integer',
        ];
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(EwsCommodityMetric::class);
    }
}
