<?php

namespace App\Models;

use App\Traits\HasPublicId;

use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    use HasPublicId; // Add public_id support
    use HasFactory;
    use HasOrganizationScope;

    // Constants for unit types
    public const TYPE_DATE = 'DATE';

    public const TYPE_DAYS_ACTIVE = 'DAYS_ACTIVE';

    public const TYPE_DAYS_CHECKED_OUT = 'DAYS_CHECKED_OUT';

    public const TYPE_QUANTITY = 'QUANTITY';

    public const TYPE_DISTANCE = 'DISTANCE';

    public const TYPE_WEIGHT = 'WEIGHT';

    public const TYPE_VOLUME = 'VOLUME';

    protected $fillable = [
        'org_id',
        'name',
        'code',
        'symbol',
        'description',
        'type',
        'is_active',
    ];

    protected static function getEntityType(): string
    {
        return 'unit_of_measure';
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
        return $this->hasMany(Item::class, 'unit_id');
    }

    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'unit_of_measure_id');
    }
}
