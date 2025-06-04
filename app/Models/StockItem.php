<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    protected $fillable = [
        'org_id',
        'stock_id',
        'item_id',
        'serial_number',
        'barcode',
        'quantity',
        'status_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function getEntityType(): string
    {
        return 'stock_item';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ItemStatus::class, 'status_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'stock_item_id');
    }

    public function checkInOuts(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'stock_item_id');
    }

    public function stockItemLocations(): HasMany
    {
        return $this->hasMany(StockItemLocation::class, 'stock_item_id');
    }

    public function getIsCheckedOutAttribute(): bool
    {
        return $this->checkInOuts()
            ->whereNull('checkin_date')
            ->exists();
    }

    public function getCurrentCheckOutAttribute()
    {
        return $this->checkInOuts()
            ->whereNull('checkin_date')
            ->latest('checkout_date')
            ->first();
    }
}
