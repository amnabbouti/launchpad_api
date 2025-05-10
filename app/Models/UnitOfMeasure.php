<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'description',
        'type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Constants for unit types
    public const TYPE_DATE = 'DATE';
    public const TYPE_DAYS_ACTIVE = 'DAYS_ACTIVE';
    public const TYPE_DAYS_CHECKED_OUT = 'DAYS_CHECKED_OUT';
    public const TYPE_QUANTITY = 'QUANTITY';
    public const TYPE_DISTANCE = 'DISTANCE';
    public const TYPE_WEIGHT = 'WEIGHT';
    public const TYPE_VOLUME = 'VOLUME';

    // Maintenance conditions using this unit
    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class);
    }
}
