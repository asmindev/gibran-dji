<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class IncomingItem extends Model
{
    protected $fillable = [
        'transaction_id',
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
            // Observer dinonaktifkan sementara untuk import - stock increment dilakukan manual di import
            // Log::info('IncomingItem created: Item ID ' . $incomingItem->item_id . ' - Quantity: ' . $incomingItem->quantity);
            // $incomingItem->item->increment('stock', $incomingItem->quantity);
            // Log::info('Stock incremented for Item ID ' . $incomingItem->item_id . ' by ' . $incomingItem->quantity);
        });

        static::updated(function (IncomingItem $incomingItem) {
            // Handle stock adjustment when incoming item is updated
            $original = $incomingItem->getOriginal();
            $difference = $incomingItem->quantity - $original['quantity'];
            Log::info('IncomingItem updated: Item ID ' . $incomingItem->item_id . ' - Difference: ' . $difference);
            $incomingItem->item->increment('stock', $difference);
        });

        static::deleted(function (IncomingItem $incomingItem) {
            // Decrease stock when incoming item is deleted
            Log::info('IncomingItem deleted: Item ID ' . $incomingItem->item_id . ' - Quantity: ' . $incomingItem->quantity);
            $incomingItem->item->decrement('stock', $incomingItem->quantity);
        });
    }
}
