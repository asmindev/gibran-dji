<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OutgoingItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'outgoing_date',
        'item_id',
        'quantity',
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
        'outgoing_date' => 'date',
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
