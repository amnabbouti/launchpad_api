<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCategory extends Model
{
    protected $fillable = [
        'remarks',
        'is_active',
    ];

    public function maintenanceConditions(): HasMany
    {
        return $this->hasMany(MaintenanceCondition::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(Description::class);
    }
}
