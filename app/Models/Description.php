<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Description extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'description_string',
        'language',
        'is_active',
        'maintenance_category_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Maintenance category relationship
    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }
}
