<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckInOut extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;

    protected $table = 'check_ins_outs';

    protected $fillable = [
        'org_id',
        'user_id',
        'stock_item_id',
        'checkout_location_id',
        'checkout_date',
        'quantity',
        'status_out_id',
        'checkin_user_id',
        'checkin_location_id',
        'checkin_date',
        'checkin_quantity',
        'status_in_id',
        'expected_return_date',
        'reference',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'checkout_date' => 'datetime',
        'checkin_date' => 'datetime',
        'expected_return_date' => 'datetime',
        'quantity' => 'decimal:2',
        'checkin_quantity' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function checkinUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checkin_user_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function checkoutLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'checkout_location_id');
    }

    public function checkinLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'checkin_location_id');
    }

    public function statusOut(): BelongsTo
    {
        return $this->belongsTo(ItemStatus::class, 'status_out_id');
    }

    public function statusIn(): BelongsTo
    {
        return $this->belongsTo(ItemStatus::class, 'status_in_id');
    }

    public function getIsCheckedInAttribute(): bool
    {
        return ! is_null($this->checkin_date);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->expected_return_date
               && $this->expected_return_date->isPast()
               && is_null($this->checkin_date);
    }

    public function getIsCheckedOutAttribute(): bool
    {
        return $this->checkin_date === null;
    }
}
