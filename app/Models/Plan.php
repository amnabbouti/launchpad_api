<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'name',
        'price',
        'user_limit',
        'features',
        'interval',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected static function getEntityType(): string
    {
        return 'plan';
    }

    /**
     * Get the licenses for this plan.
     */
    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    /**
     * Get the organizations using this plan (direct assignment).
     */
    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }
}
