<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'last_used_at',
        // Custom fields for API key management
        'description',
        'organization_id',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'rate_limit_per_month',
        'allowed_ips',
        'allowed_origins',
        'key_type',
        'metadata',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'allowed_ips' => 'json',
        'allowed_origins' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'rate_limit_per_hour' => 'integer',
        'rate_limit_per_day' => 'integer',
        'rate_limit_per_month' => 'integer',
    ];

    /**
     * Get the usage logs for this token
     */
    public function usageLogs()
    {
        return $this->hasMany(ApiKeyUsage::class, 'token_id');
    }

    /**
     * Get the organization that owns this token
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
