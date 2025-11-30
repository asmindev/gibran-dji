<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    /**
     * Generate unique transaction ID with format TR{YYYYMMDD}{XXX}
     * Example: TR202511300001
     */
    public static function generateTransactionId($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        $datePrefix = 'TR' . $date->format('Ymd');

        // Get last transaction ID for today
        $lastTransaction = self::where('transaction_id', 'LIKE', $datePrefix . '%')
            ->orderBy('transaction_id', 'desc')
            ->first();

        if ($lastTransaction) {
            // Extract sequence number and increment
            $lastSequence = intval(substr($lastTransaction->transaction_id, -4));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $datePrefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

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
