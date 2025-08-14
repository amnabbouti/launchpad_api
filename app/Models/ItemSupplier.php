<?php

declare(strict_types = 1);

namespace App\Models;

use App\Constants\AppConstants;
use App\Constants\ErrorMessages;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ItemSupplier extends Model {
    use HasFactory;
    use HasUuidv7;

    protected $casts = [
        'price'          => 'decimal:2',
        'lead_time_days' => 'integer',
        'is_preferred'   => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

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

    protected $table = 'item_supplier';

    public static function rules(): array {
        return [
            'price'          => 'nullable|numeric|min:0|max:' . AppConstants::ITEM_MAX_PRICE,
            'lead_time_days' => 'nullable|integer|min:0',
        ];
    }

    public function item(): BelongsTo {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function supplier(): BelongsTo {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected static function booted(): void {
        self::saving(static function ($itemSupplier): void {
            if ($itemSupplier->price !== null && $itemSupplier->price < 0) {
                throw new InvalidArgumentException(__(ErrorMessages::NEGATIVE_PRICE));
            }

            if ($itemSupplier->lead_time_days !== null && $itemSupplier->lead_time_days < 0) {
                throw new InvalidArgumentException(__(ErrorMessages::NEGATIVE_LEAD_TIME));
            }
        });
    }
}
