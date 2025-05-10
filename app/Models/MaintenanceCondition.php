<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCondition extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
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
        'unit_of_measure_id'
    ];

    protected $casts = [
        'mail_on_warning' => 'boolean',
        'mail_on_maintenance' => 'boolean',
        'maintenance_warning_date' => 'datetime',
        'maintenance_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Check if maintenance is recurring
    public function getIsRecurringAttribute(): bool
    {
        return $this->recurrence_unit !== null && $this->maintenance_recurrence_quantity > 0;
    }

    // Item relationship
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // Status when returned relationship
    public function statusWhenReturned(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_when_returned_id');
    }

    // Status when exceeded relationship
    public function statusWhenExceeded(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_when_exceeded_id');
    }

    // Maintenance category relationship
    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }

    // Unit of measure relationship
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    // Maintenance details relationship
    public function maintenanceDetails(): HasMany
    {
        return $this->hasMany(MaintenanceDetail::class);
    }
}
