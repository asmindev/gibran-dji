<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingItem extends Model
{
    protected $fillable = [
        'incoming_date',
        'item_id',
        'quantity',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'incoming_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    protected static function booted(): void
    {
        static::created(function (IncomingItem $incomingItem) {
            // Automatically increase item stock when incoming item is created
            $incomingItem->item->increment('stock', $incomingItem->quantity);
        });

        static::updated(function (IncomingItem $incomingItem) {
            // Handle stock adjustment when incoming item is updated
            $original = $incomingItem->getOriginal();
            $difference = $incomingItem->quantity - $original['quantity'];
            $incomingItem->item->increment('stock', $difference);
        });

        static::deleted(function (IncomingItem $incomingItem) {
            // Decrease stock when incoming item is deleted
            $incomingItem->item->decrement('stock', $incomingItem->quantity);
        });
    }
}
