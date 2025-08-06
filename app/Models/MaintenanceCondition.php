<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCondition extends Model
{
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    protected $fillable = [
        'org_id',
        'mail_on_warning',
        'mail_on_maintenance',
        'maintenance_recurrence_quantity',
        'maintenance_warning_date',
        'maintenance_date',
        'quantity_for_warning',
        'quantity_for_maintenance',
        'recurrence_unit',
        'price_per_unit',
        'is_active',
        'item_id',
        'status_when_returned_id',
        'status_when_exceeded_id',
        'maintenance_category_id',
        'unit_of_measure_id',
    ];

    protected static function getEntityType(): string
    {
        return 'maintenance_condition';
    }

    protected $casts = [
        'mail_on_warning' => 'boolean',
        'mail_on_maintenance' => 'boolean',
        'maintenance_recurrence_quantity' => 'integer',
        'maintenance_warning_date' => 'datetime',
        'maintenance_date' => 'datetime',
        'quantity_for_warning' => 'decimal:2',
        'quantity_for_maintenance' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function statusWhenReturned(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_when_returned_id');
    }

    public function statusWhenExceeded(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_when_exceeded_id');
    }

    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class, 'maintenance_category_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }

    public function maintenanceDetails(): HasMany
    {
        return $this->hasMany(MaintenanceDetail::class, 'maintenance_condition_id');
    }

    public function getIsRecurringAttribute(): bool
    {
        return $this->recurrence_unit !== null && $this->maintenance_recurrence_quantity > 0;
    }
}
