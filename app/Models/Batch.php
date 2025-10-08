<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;

    protected $casts = [
        'unit_cost'     => 'decimal:2',
        'received_date' => 'date',
        'expiry_date'   => 'date',
        'is_active'     => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

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

    public function checkInOuts(): HasMany {
        return $this->hasMany(CheckInOut::class, 'batch_id');
    }

    public function getIsActiveAndNotExpiredAttribute(): bool {
        return $this->is_active && ! $this->getIsExpiredAttribute();
    }

    public function getIsExpiredAttribute(): bool {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function items(): HasMany {
        return $this->hasMany(Item::class, 'batch_id');
    }

    public function maintenances(): HasMany {
        return $this->hasMany(Maintenance::class, 'batch_id');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function supplier(): BelongsTo {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
