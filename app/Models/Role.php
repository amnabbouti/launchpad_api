<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function in_array;
use function is_string;

class Role extends Model {
    use HasFactory;
    use HasUuidv7;

    protected $casts = [
        'forbidden'  => 'array',
        'is_system'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'slug',
        'title',
        'description',
        'forbidden',
        'org_id',
        'is_system',
    ];

    public function allows(string $action): bool {
        return ! $this->forbids($action);
    }

    public function forbids(string $action): bool {
        $forbidden = $this->forbidden ?? [];

        return in_array($action, $forbidden, true);
    }

    public function getForbidden(): array {
        $forbidden = $this->forbidden;

        if (is_string($forbidden)) {
            return json_decode($forbidden, true) ?? [];
        }

        return $forbidden ?? [];
    }

    public function getTypeAttribute(): string {
        return $this->isSystemRole() ? 'System Role' : 'Custom Role';
    }

    public function isCustomRole(): bool {
        return ! $this->isSystemRole();
    }

    public function isSystemRole(): bool {
        return $this->is_system === true;
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function scopeCustomRoles($query) {
        return $query->where('is_system', false);
    }

    public function scopeSystemRoles($query) {
        return $query->where('is_system', true);
    }

    public function users(): HasMany {
        return $this->hasMany(User::class, 'role_id');
    }
}
