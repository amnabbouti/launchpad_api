<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasPublicId;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasAttachments;
    use HasPublicId;
    use HasFactory;
    use SoftDeletes;

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
        'plan_id',
        'subscription_starts_at',
        'subscription_ends_at',
        'settings',
        'created_by',
    ];

    protected static function getEntityType(): string
    {
        return 'organization';
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'subscription_starts_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'settings' => 'array',
        'plan_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * The plan this organization is assigned to (direct assignment).
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * All licenses purchased by this organization.
     */
    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    /**
     * The user who created this organization (if tracked).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'org_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'org_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'org_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'org_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'org_id');
    }

    public function unitOfMeasures(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'org_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class, 'org_id');
    }

    public function maintenanceCategories(): HasMany
    {
        return $this->hasMany(MaintenanceCategory::class, 'org_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'org_id');
    }

    public function checkInOuts(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'org_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'org_id');
    }

    /**
     * Total seats from active licenses (started, not expired)
     */
    public function activeLicenseSeatCount(): int
    {
        $now = now();
        return $this->licenses()
            ->where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->sum('seats');
    }

    /**
     * Number of active users in org
     */
    public function activeUserCount(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    /**
     * Can org add another user?
     */
    public function hasAvailableSeats(): bool
    {
        return $this->activeUserCount() < $this->activeLicenseSeatCount();
    }
}
