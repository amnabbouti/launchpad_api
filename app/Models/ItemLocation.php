<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ItemLocation extends Model
{
    use SoftDeletes;

    protected $table = 'item_location';

    protected $fillable = [
        'item_id',
        'location_id',
        'quantity'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public static function rules(): array
    {
        return [
            'quantity' => 'numeric|min:0', // Ensure quantity is non-negative
        ];
    }

    protected static function booted(): void
    {
        // Validate before saving
        static::saving(function ($itemLocation) {
            if ($itemLocation->quantity < 0) {
                throw new \InvalidArgumentException('Item location quantity cannot be negative.');
            }
        });

        // Update the item's total quantity when a record is created
        static::created(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });

        // Update the item's total quantity when a record is updated
        static::updated(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });

        // Update the item's total quantity when a record is soft deleted
        static::deleted(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });

        // Update the item's total quantity when a record is restored
        static::restored(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });
    }

    protected static function updateItemQuantity(int $itemId): void
    {
        // Use a transaction to ensure data integrity
        DB::transaction(function () use ($itemId) {
            // Calculate the sum of quantities across all non-deleted item_location records
            $totalQuantity = static::where('item_id', $itemId)
                ->whereNull('deleted_at')
                ->sum('quantity');

            // Update the item's quantity
            Item::where('id', $itemId)->update(['quantity' => $totalQuantity]);
        });
    }
}
