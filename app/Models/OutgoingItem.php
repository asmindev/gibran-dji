<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'outgoing_date',
        'item_id',
        'quantity',
        'unit_price',
        'customer',
        'notes',
    ];

    protected $casts = [
        'outgoing_date' => 'date',
        'unit_price' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    protected static function booted(): void
    {
        static::created(function (OutgoingItem $outgoingItem) {
            // Automatically decrease item stock when outgoing item is created
            $outgoingItem->item->decrement('stock', $outgoingItem->quantity);
        });

        static::updated(function (OutgoingItem $outgoingItem) {
            // Handle stock adjustment when outgoing item is updated
            $original = $outgoingItem->getOriginal();
            $difference = $outgoingItem->quantity - $original['quantity'];
            $outgoingItem->item->decrement('stock', $difference);
        });

        static::deleted(function (OutgoingItem $outgoingItem) {
            // Increase stock when outgoing item is deleted
            $outgoingItem->item->increment('stock', $outgoingItem->quantity);
        });
    }
}
