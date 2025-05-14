<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'value',
        'maintenance_condition_id',
        'maintenance_id',
    ];

    // Maintenance condition relationship
    public function maintenanceCondition(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCondition::class);
    }

    // Maintenance relationship
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}
