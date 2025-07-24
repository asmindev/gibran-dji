<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class InventoryRecommendation extends Model
{
    protected $fillable = [
        'antecedent_items',
        'consequent_items',
        'support',
        'confidence',
        'lift',
        'rule_description',
        'is_active',
        'analyzed_at',
    ];

    protected $casts = [
        'support' => 'decimal:4',
        'confidence' => 'decimal:4',
        'lift' => 'decimal:4',
        'is_active' => 'boolean',
        'analyzed_at' => 'datetime',
    ];

    protected function antecedentItems(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => json_decode($value, true),
            set: fn(array $value) => json_encode($value),
        );
    }

    protected function consequentItems(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => json_decode($value, true),
            set: fn(array $value) => json_encode($value),
        );
    }

    public function getAntecedentItemsListAttribute(): string
    {
        return implode(', ', $this->antecedent_items);
    }

    public function getConsequentItemsListAttribute(): string
    {
        return implode(', ', $this->consequent_items);
    }

    public function getAntecedentItemsNamesAttribute(): string
    {
        $itemCodes = $this['antecedent_items'];
        $items = Item::whereIn('item_code', $itemCodes)->pluck('item_name')->toArray();
        return implode(', ', $items);
    }

    public function getConsequentItemsNamesAttribute(): string
    {
        $itemCodes = $this['consequent_items'];
        $items = Item::whereIn('item_code', $itemCodes)->pluck('item_name')->toArray();
        return implode(', ', $items);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighConfidence($query, $threshold = 0.7)
    {
        return $query->where('confidence', '>=', $threshold);
    }
}
