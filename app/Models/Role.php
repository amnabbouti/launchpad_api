<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'forbidden',
    ];

    protected $casts = [
        'forbidden' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Users that have this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Check if role forbids a specific action.
     */
    public function forbids(string $action): bool
    {
        $forbidden = $this->forbidden ?? [];

        return in_array($action, $forbidden);
    }

    /**
     * Check if role allows a specific action (not forbidden).
     */
    public function allows(string $action): bool
    {
        return ! $this->forbids($action);
    }

    /**
     * Get all forbidden actions for this role.
     */
    public function getForbidden(): array
    {
        $forbidden = $this->forbidden;

        if (is_string($forbidden)) {
            return json_decode($forbidden, true) ?? [];
        }

        return $forbidden ?? [];
    }
}
