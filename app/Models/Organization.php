<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model {
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;
    use SoftDeletes;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'settings'   => 'array',
        'license_id' => 'string',
        'created_by' => 'integer',
    ];

    protected $fillable = [
        'name',
        'email',
        'telephone',
        'street',
        'street_number',
        'city',
        'province',
        'postal_code',
        'remarks',
        'website',
        'logo',
        'industry',
        'tax_id',
        'billing_address',
        'country',
        'timezone',
        'status',
        'license_id',
        'settings',
        'created_by',
        'stripe_id',
    ];

    public function activeUserCount(): int {
        return $this->users()->count();
    }

    public function attachments(): HasMany {
        return $this->hasMany(Attachment::class, 'org_id');
    }

    public function batches(): HasMany {
        return $this->hasMany(Batch::class, 'org_id');
    }

    public function categories(): HasMany {
        return $this->hasMany(Category::class, 'org_id');
    }

    public function checkInOuts(): HasMany {
        return $this->hasMany(CheckInOut::class, 'org_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasActiveLicense(): bool {
        if (! $this->license) {
            return false;
        }

        $license = $this->license;
        $now     = now();

        return $license->status === 'active'
            && $license->starts_at && $license->starts_at <= $now
            && (! $license->ends_at || $license->ends_at > $now);
    }

    public function hasAvailableSeats(): bool {
        return $this->activeUserCount() < $this->totalAvailableSeats();
    }

    public function items(): HasMany {
        return $this->hasMany(Item::class, 'org_id');
    }

    public function license() {
        return $this->belongsTo(License::class);
    }

    public function licenses() {
        return $this->hasMany(License::class, 'org_id');
    }

    public function locations(): HasMany {
        return $this->hasMany(Location::class, 'org_id');
    }

    public function maintenanceCategories(): HasMany {
        return $this->hasMany(MaintenanceCategory::class, 'org_id');
    }

    public function maintenances(): HasMany {
        return $this->hasMany(Maintenance::class, 'org_id');
    }

    public function statuses(): HasMany {
        return $this->hasMany(Status::class, 'org_id');
    }

    public function suppliers(): HasMany {
        return $this->hasMany(Supplier::class, 'org_id');
    }

    public function totalAvailableSeats(): int {
        if (! $this->license) {
            return 0;
        }

        $license  = $this->license;
        $now      = now();
        $isActive = $license->status === 'active'
            && $license->starts_at && $license->starts_at <= $now
            && (! $license->ends_at || $license->ends_at > $now);

        return $isActive ? (int) $license->seats : 0;
    }

    public function unitOfMeasures(): HasMany {
        return $this->hasMany(UnitOfMeasure::class, 'org_id');
    }

    public function users(): HasMany {
        return $this->hasMany(User::class, 'org_id');
    }
}
