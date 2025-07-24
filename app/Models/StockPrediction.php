<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrediction extends Model
{
    protected $fillable = [
        'item_id',
        'predicted_demand',
        'prediction_confidence',
        'prediction_period_start',
        'prediction_period_end',
        'feature_importance',
        'is_active',
        'analyzed_at',
    ];

    protected $casts = [
        'prediction_confidence' => 'decimal:2',
        'prediction_period_start' => 'date',
        'prediction_period_end' => 'date',
        'feature_importance' => 'array',
        'is_active' => 'boolean',
        'analyzed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('prediction_confidence', '>=', $threshold);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('prediction_period_start', [$startDate, $endDate])
            ->orWhereBetween('prediction_period_end', [$startDate, $endDate]);
    }

    public function getFormattedConfidenceAttribute(): string
    {
        return number_format($this->prediction_confidence, 1) . '%';
    }
}
