<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceDetail extends Model {
    use HasFactory;
    use HasUuidv7;

    protected $casts = [
        'value'      => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'org_id',
        'value',
        'maintenance_condition_id',
        'maintenance_id',
    ];

    public function maintenance(): BelongsTo {
        return $this->belongsTo(Maintenance::class, 'maintenance_id');
    }

    public function maintenanceCondition(): BelongsTo {
        return $this->belongsTo(MaintenanceCondition::class, 'maintenance_condition_id');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
