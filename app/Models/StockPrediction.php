<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockPrediction extends Model
{
    protected $fillable = [
        'prediction',
        'actual',
        'product',
        'month',
        'item_id',
    ];

    protected $casts = [
        'month' => 'date'
    ];

    /**
     * Calculate accuracy percentage if actual data is available
     */
    public function getAccuracyAttribute(): ?float
    {
        if ($this->actual === null || $this->prediction === 0) {
            return null;
        }

        $difference = abs($this->prediction - $this->actual);
        $accuracy = (1 - ($difference / max($this->prediction, $this->actual))) * 100;

        return max(0, round($accuracy, 2));
    }

    /**
     * Get formatted prediction month
     */
    public function getFormattedMonthAttribute(): string
    {
        return Carbon::parse($this->month)->locale('id')->format('F Y');
    }

    /**
     * Scope for specific month
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('month', $year)
            ->whereMonth('month', $month);
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, $product)
    {
        return $query->where('product', $product);
    }

    /**
     * Scope for predictions with actual data
     */
    public function scopeWithActual($query)
    {
        return $query->whereNotNull('actual');
    }

    /**
     * Scope for predictions without actual data
     */
    public function scopeWithoutActual($query)
    {
        return $query->whereNull('actual');
    }

    // Relationship to the Item model
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
