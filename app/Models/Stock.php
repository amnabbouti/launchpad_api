<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'org_id',
        'batch_number',
        'received_date',
        'expiry_date',
        'supplier_id',
        'unit_cost',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'received_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class, 'stock_id');
    }

    public function checkInOuts(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'stock_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'stock_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsActiveAndNotExpiredAttribute(): bool
    {
        return $this->is_active && ! $this->getIsExpiredAttribute();
    }
}
