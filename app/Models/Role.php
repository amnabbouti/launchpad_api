<?php

namespace App\Models;

use App\Traits\HasPublicId;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasPublicId; // Add public_id support
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'forbidden',
        'org_id',
        'is_system',
    ];

    protected static function getEntityType(): string
    {
        return 'role';
    }

    protected $casts = [
        'forbidden' => 'array',
        'is_system' => 'boolean',
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

    /**
     * Organization relationship.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Check if this is a system role.
     */
    public function isSystemRole(): bool
    {
        return $this->is_system === true;
    }

    /**
     * Check if this is a custom organization role.
     */
    public function isCustomRole(): bool
    {
        return !$this->isSystemRole();
    }

    /**
     * Get role type for display.
     */
    public function getTypeAttribute(): string
    {
        return $this->isSystemRole() ? 'System Role' : 'Custom Role';
    }

    /**
     * Scope to get only system roles.
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get only custom roles.
     */
    public function scopeCustomRoles($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to get roles for a specific organization.
     */
    public function scopeForOrganization($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}
