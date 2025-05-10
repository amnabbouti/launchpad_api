<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'remarks',
        'is_active'
    ];

    // Maintenance conditions belonging to this category
    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class);
    }

    // Descriptions for this category in different languages
    public function descriptions(): HasMany
    {
        return $this->hasMany(Description::class);
    }
}
