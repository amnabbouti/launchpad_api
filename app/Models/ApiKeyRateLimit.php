<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyRateLimit extends Model {
    protected $casts = [
        'window_start' => 'datetime',
        'window_end'   => 'datetime',
    ];

    protected $fillable = [
        'token_id',
        'requests_count',
        'requests_limit',
        'window_type',
        'window_start',
        'window_end',
    ];

    public static function checkRateLimit(PersonalAccessToken $token, string $windowType = 'hour'): array {
        $now         = now();
        $windowStart = match ($windowType) {
            'hour'  => $now->copy()->startOfHour(),
            'day'   => $now->copy()->startOfDay(),
            'month' => $now->copy()->startOfMonth(),
            default => $now->copy()->startOfHour(),
        };
        $windowEnd = match ($windowType) {
            'hour'  => $now->copy()->endOfHour(),
            'day'   => $now->copy()->endOfDay(),
            'month' => $now->copy()->endOfMonth(),
            default => $now->copy()->endOfHour(),
        };

        $rateLimit = self::firstOrCreate([
            'token_id'     => $token->id,
            'window_type'  => $windowType,
            'window_start' => $windowStart,
        ], [
            'requests_count' => 0,
            'requests_limit' => self::getTokenLimit($token, $windowType),
            'window_end'     => $windowEnd,
        ]);

        return [
            'exceeded'   => $rateLimit->requests_count >= $rateLimit->requests_limit,
            'current'    => $rateLimit->requests_count,
            'limit'      => $rateLimit->requests_limit,
            'reset_time' => $rateLimit->window_end,
        ];
    }

    public static function incrementUsage(PersonalAccessToken $token, string $windowType = 'hour'): void {
        $now         = now();
        $windowStart = match ($windowType) {
            'hour'  => $now->copy()->startOfHour(),
            'day'   => $now->copy()->startOfDay(),
            'month' => $now->copy()->startOfMonth(),
            default => $now->copy()->startOfHour(),
        };

        self::where('token_id', $token->id)
            ->where('window_type', $windowType)
            ->where('window_start', $windowStart)
            ->increment('requests_count');
    }

    public function token(): BelongsTo {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    private static function getTokenLimit(PersonalAccessToken $token, string $windowType): int {
        return match ($windowType) {
            'hour'  => $token->rate_limit_per_hour ?? 1000,
            'day'   => $token->rate_limit_per_day ?? 24000,
            'month' => $token->rate_limit_per_month ?? 720000,
            default => 1000,
        };
    }
}
