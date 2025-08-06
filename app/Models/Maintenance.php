<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Maintenance extends Model
{
    use HasAttachments;
    use HasFactory;
    use HasOrganizationScope;
    use HasPublicId;

    protected $fillable = [
        'org_id',
        'remarks',
        'cost',
        'date_expected_back_from_maintenance',
        'date_back_from_maintenance',
        'date_in_maintenance',
        'is_repair',
        'import_id',
        'import_source',
        'user_id',
        'supplier_id',
        'maintainable_id',
        'maintainable_type',
        'status_out_id',
        'status_in_id',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date_expected_back_from_maintenance' => 'datetime',
        'date_back_from_maintenance' => 'datetime',
        'date_in_maintenance' => 'datetime',
        'is_repair' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function getEntityType(): string
    {
        return 'maintenance';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maintainable(): MorphTo
    {
        return $this->morphTo();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function statusOut(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_out_id');
    }

    public function statusIn(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_in_id');
    }

    public function maintenanceDetails(): HasMany
    {
        return $this->hasMany(MaintenanceDetail::class, 'maintenance_id');
    }
}
