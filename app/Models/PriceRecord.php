<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity_id',
        'recorded_at',
        'market_name',
        'price',
        'source',
    ];

    protected $casts = [
        'recorded_at' => 'date',
        'price' => 'decimal:2',
    ];

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}