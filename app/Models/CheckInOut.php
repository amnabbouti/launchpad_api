<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckInOut extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'check_ins_outs';

    protected $fillable = [
        'user_id',
        'item_id',
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
    ];

    // Checkout user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Checkin user
    public function checkinUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checkin_user_id');
    }

    // Item
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // Checkout location
    public function checkoutLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'checkout_location_id');
    }

    // Checkin location
    public function checkinLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'checkin_location_id');
    }

    // Status out
    public function statusOut(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_out_id');
    }

    // Status in
    public function statusIn(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_in_id');
    }

    // Check out status
    public function getIsCheckedOutAttribute(): bool
    {
        return $this->checkin_date === null;
    }
}
