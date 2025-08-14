<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserToken extends Model {
    use HasUuidv7;

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'token_type',
        'plain_text_token',
        'personal_access_token_id',
        'is_active',
        'last_used_at',
    ];

    public function markAsUsed(): void {
        $this->update(['last_used_at' => now()]);
    }

    public function personalAccessToken(): BelongsTo {
        return $this->belongsTo(PersonalAccessToken::class);
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type) {
        return $query->where('token_type', $type);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
