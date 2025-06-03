<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSupplier extends Model
{
    use HasFactory;
    use HasOrganizationScope;

    protected $table = 'item_supplier';

    protected $fillable = [
        'org_id',
        'item_id',
        'supplier_id',
        'supplier_part_number',
        'price',
        'currency',
        'lead_time_days',
        'is_preferred',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'lead_time_days' => 'integer',
        'is_preferred' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function rules(): array
    {
        return [
            'price' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected static function booted(): void
    {
        static::saving(function ($itemSupplier) {
            if ($itemSupplier->price !== null && $itemSupplier->price < 0) {
                throw new \InvalidArgumentException('Price cannot be negative.');
            }

            if ($itemSupplier->lead_time_days !== null && $itemSupplier->lead_time_days < 0) {
                throw new \InvalidArgumentException('Lead time cannot be negative.');
            }
        });
    }
}
