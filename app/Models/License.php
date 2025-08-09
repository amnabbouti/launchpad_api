<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'org_id',
        'name',
        'price',
        'seats',
        'license_key',
        'starts_at',
        'ends_at',
        'status',
        'features',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'features' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'decimal:2',
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
                do {
                    $key = 'LIC-' . strtoupper(Str::random(16));
                } while (self::where('license_key', $key)->exists());
                $license->license_key = $key;
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
