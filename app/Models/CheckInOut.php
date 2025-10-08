<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CheckInOut extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;

    protected $casts = [
        'checkout_date'        => 'datetime',
        'checkin_date'         => 'datetime',
        'expected_return_date' => 'datetime',
        'quantity'             => 'decimal:2',
        'checkin_quantity'     => 'decimal:2',
        'is_active'            => 'boolean',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'user_id',
        'trackable_id',
        'trackable_type',
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

    protected $table = 'check_ins_outs';

    public function checkinLocation(): BelongsTo {
        return $this->belongsTo(Location::class, 'checkin_location_id');
    }

    public function checkinUser(): BelongsTo {
        return $this->belongsTo(User::class, 'checkin_user_id');
    }

    public function checkoutLocation(): BelongsTo {
        return $this->belongsTo(Location::class, 'checkout_location_id');
    }

    public function getIsCheckedInAttribute(): bool {
        return $this->checkin_date !== null;
    }

    public function getIsCheckedOutAttribute(): bool {
        return $this->checkin_date === null;
    }

    public function getIsOverdueAttribute(): bool {
        return $this->expected_return_date
               && $this->expected_return_date->isPast()
               && $this->checkin_date === null;
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function statusIn(): BelongsTo {
        return $this->belongsTo(Status::class, 'status_in_id');
    }

    public function statusOut(): BelongsTo {
        return $this->belongsTo(Status::class, 'status_out_id');
    }

    public function trackable(): MorphTo {
        return $this->morphTo();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }
}
