<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use function count;

class User extends Authenticatable {
    use HasApiTokens;
    use HasAttachments;
    use HasFactory;
    use HasUuidv7;
    use Notifiable;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'date_of_birth'     => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

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

    public function attachments(): HasMany {
        return $this->hasMany(Attachment::class, 'user_id');
    }

    public function getCurrentRoleSlug(): ?string {
        if ($this->role_id && $this->role) {
            return $this->role->slug;
        }

        return $this->org_role ?? 'employee';
    }

    public function getCurrentRoleTitle(): ?string {
        return $this->role?->title ?? 'Employee';
    }

    public function getFullNameAttribute(): string {
        return mb_trim("{$this->first_name} {$this->last_name}");
    }

    public function getName(): string {
        return mb_trim($this->first_name . ' ' . $this->last_name);
    }

    public function hasPermission(string $action): bool {
        $roleSlug = $this->getCurrentRoleSlug();

        if (! $roleSlug) {
            return false;
        }

        $parts = explode('.', $action, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$resource, $actionPart] = $parts;

        return ! \App\Services\AuthorizationEngine::isForbidden($actionPart, $resource, $this);
    }

    public function isEmployee(): bool {
        return $this->getCurrentRoleSlug() === 'employee';
    }

    public function isManager(): bool {
        return $this->getCurrentRoleSlug() === 'manager';
    }

    public function isSuperAdmin(): bool {
        return $this->getCurrentRoleSlug() === 'super_admin';
    }

    public function items(): HasMany {
        return $this->hasMany(Item::class, 'user_id');
    }

    public function lacksPermission(string $action): bool {
        return ! $this->hasPermission($action);
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function role(): BelongsTo {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
