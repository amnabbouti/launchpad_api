<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'seats',
        'license_key',
        'starts_at',
        'ends_at',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function getEntityType(): string
    {
        return 'license';
    }

    /**
     * Boot method to auto-generate license_key if not set.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($license) {
            if (empty($license->license_key)) {
                $license->license_key = strtoupper(Str::random(20));
            }
        });
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
