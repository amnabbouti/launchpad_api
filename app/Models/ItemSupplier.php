<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSupplier extends Model
{
    use SoftDeletes;

    protected $table = 'item_supplier';

    protected $fillable = [
        'item_id',
        'supplier_id',
        'supplier_part_number',
        'price',
        'lead_time',
        'is_preferred'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'lead_time' => 'integer',
        'is_preferred' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public static function rules(): array
    {
        return [
            'price' => 'nullable|numeric|min:0',
            'lead_time' => 'nullable|integer|min:0',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($itemSupplier) {
            if ($itemSupplier->price !== null && $itemSupplier->price < 0) {
                throw new \InvalidArgumentException('Price cannot be negative.');
            }
            
            if ($itemSupplier->lead_time !== null && $itemSupplier->lead_time < 0) {
                throw new \InvalidArgumentException('Lead time cannot be negative.');
            }
        });
    }
}
