<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ItemLocation extends Model
{
    use SoftDeletes;

    protected $table = 'item_location';

    protected $fillable = [
        'item_id',
        'location_id',
        'quantity',
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
            'quantity' => 'numeric|min:0',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($itemLocation) {
            if ($itemLocation->quantity < 0) {
                throw new \InvalidArgumentException('Item location quantity cannot be negative.');
            }
        });

        static::created(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });

        static::updated(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });

        static::deleted(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });

        static::restored(function ($itemLocation) {
            static::updateItemQuantity($itemLocation->item_id);
        });
    }

    protected static function updateItemQuantity(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $totalQuantity = static::where('item_id', $itemId)
                ->whereNull('deleted_at')
                ->sum('quantity');

            Item::where('id', $itemId)->update(['quantity' => $totalQuantity]);
        });
    }
}
