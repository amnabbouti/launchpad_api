<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCategory extends Model
{
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    protected $fillable = [
        'org_id',
        'name',
        'remarks',
        'is_active',
    ];

    protected static function getEntityType(): string
    {
        return 'maintenance_category';
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

    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class, 'maintenance_category_id');
    }
}
