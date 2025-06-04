<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasPublicId; // Add public_id support
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'is_active',
    ];

    protected static function getEntityType(): string
    {
        return 'status';
    }

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'status_id');
    }

    public function maintenancesOut(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'status_out_id');
    }

    public function maintenancesIn(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'status_in_id');
    }

    public function checkInOutsOut(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'status_out_id');
    }

    public function checkInOutsIn(): HasMany
    {
        return $this->hasMany(CheckInOut::class, 'status_in_id');
    }

    public function maintenanceConditionsReturned(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'status_when_returned_id');
    }

    public function maintenanceConditionsExceeded(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'status_when_exceeded_id');
    }
}
