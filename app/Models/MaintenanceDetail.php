<?php

namespace App\Models;

use App\Traits\HasPublicId;

use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceDetail extends Model
{
    use HasPublicId; // Add public_id support
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'org_id',
        'value',
        'maintenance_condition_id',
        'maintenance_id',
    ];

    protected static function getEntityType(): string
    {
        return 'maintenance_detail';
    }

    protected $casts = [
        'value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function maintenanceCondition(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCondition::class, 'maintenance_condition_id');
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_id');
    }
}
