<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'item_code',
        'item_name',
        'category_id',
        'stock',
        'minimum_stock',
        'purchase_price',
        'selling_price',
        'description',
        'image_path',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function incomingItems(): HasMany
    {
        return $this->hasMany(IncomingItem::class);
    }

    public function outgoingItems(): HasMany
    {
        return $this->hasMany(OutgoingItem::class);
    }

    public function isLowStock($threshold = 10): bool
    {
        return $this->stock <= $threshold;
    }

    // Accessor for name to map to item_name
    public function getNameAttribute(): string
    {
        return $this->item_name;
    }
}
