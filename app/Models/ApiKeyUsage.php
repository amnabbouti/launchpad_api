<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyUsage extends Model {
    protected $casts = [
        'request_data'  => 'array',
        'usage_date'    => 'date',
        'response_time' => 'decimal:3',
    ];

    protected $fillable = [
        'token_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'response_status',
        'response_time',
        'request_data',
        'usage_date',
    ];

    protected $table = 'api_key_usage';

    public static function logUsage(
        PersonalAccessToken $token,
        string $endpoint,
        string $method,
        string $ipAddress,
        string $userAgent,
        int $responseStatus,
        float $responseTime,
        array $requestData = [],
    ): self {
        return self::create([
            'token_id'        => $token->id,
            'endpoint'        => $endpoint,
            'method'          => $method,
            'ip_address'      => $ipAddress,
            'user_agent'      => $userAgent,
            'response_status' => $responseStatus,
            'response_time'   => $responseTime,
            'request_data'    => $requestData,
            'usage_date'      => now()->toDateString(),
        ]);
    }

    public function personalAccessToken(): BelongsTo {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    public function token(): BelongsTo {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }
}
