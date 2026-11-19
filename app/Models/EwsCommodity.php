<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EwsCommodity extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'reference_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reference_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(EwsCommodityMetric::class);
    }
}
