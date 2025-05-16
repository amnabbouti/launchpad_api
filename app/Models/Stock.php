<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'serial_number',
        'barcode',
        'purchase_price',
        'purchase_date',
        'warranty_end_date',
        'notes',
        'is_active',
        'location_id',
        'status_id',
    ];


    protected $casts = [
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Items relationship - Stock can have multiple Items
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    // Location relationship
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // Status relationship
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // Check-ins/outs relationship
    public function checkInOuts(): HasMany
    {
        return $this->hasMany(CheckInOut::class);
    }

    // Maintenances relationship
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    // Check if item is currently checked out
    public function getIsCheckedOutAttribute(): bool
    {
        return $this->checkInOuts()
            ->whereNull('checkin_date')
            ->exists();
    }

    // Get current check-out record if any
    public function getCurrentCheckOutAttribute()
    {
        return $this->checkInOuts()
            ->whereNull('checkin_date')
            ->latest('checkout_date')
            ->first();
    }
}
