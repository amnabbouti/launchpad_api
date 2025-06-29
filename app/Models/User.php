<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasPublicId;

use App\Traits\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasAttachments;
    use HasPublicId;
    use HasApiTokens;
    use HasFactory;
    use HasOrganizationScope;
    use Notifiable;

    protected $fillable = [
        'org_id',
        'first_name',
        'last_name',
        'org_role',
        'email',
        'password',
        'date_of_birth',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'phone_number',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function getEntityType(): string
    {
        return 'user';
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->getCurrentRoleSlug() === 'super_admin';
    }

    public function isManager(): bool
    {
        return $this->getCurrentRoleSlug() === 'manager';
    }

    public function isEmployee(): bool
    {
        return $this->getCurrentRoleSlug() === 'employee';
    }

    /**
     * Get the current role slug (system role or custom role).
     */
    public function getCurrentRoleSlug(): ?string
    {
        // Always use the database role now (both system and custom roles are in DB)
        if ($this->role_id && $this->role) {
            return $this->role->slug;
        }

        // Fallback to org_role field for backward compatibility (should not happen after migration)
        return $this->org_role ?? 'employee';
    }

    /**
     * Get the current role title for display.
     */
    public function getCurrentRoleTitle(): ?string
    {
        // Always use the database role title (both system and custom roles are in DB)
        return $this->role?->title ?? 'Employee';
    }

    public function hasPermission(string $action): bool
    {
        $roleSlug = $this->getCurrentRoleSlug();
        
        if (!$roleSlug) {
            return false;
        }

        // Parse action to extract resource and action parts
        // e.g., "roles.create" -> resource="roles", action="create"
        $parts = explode('.', $action, 2);
        if (count($parts) !== 2) {
            return false; // Invalid action format
        }
        
        [$resource, $actionPart] = $parts;

        // Use AuthorizationEngine to check permissions with correct resource
        return !\App\Services\AuthorizationEngine::isForbidden($actionPart, $resource, $this);
    }

    public function lacksPermission(string $action): bool
    {
        return ! $this->hasPermission($action);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the user's full name.
     */
    public function getName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
