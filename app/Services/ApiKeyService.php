<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\ApiKeyUsage;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

use function array_key_exists;
use function is_array;

class ApiKeyService {
    public function createApiKey(array $data): array {
        $user = User::findOrFail($data['user_id']);

        // Generate a more secure token name if not provided
        $tokenName = $data['name'] ?? 'API Key ' . now()->format('Y-m-d H:i:s');

        // Create the token
        $token = $user->createToken($tokenName, $data['abilities'] ?? ['*']);

        // Update the token with additional metadata
        $personalAccessToken = $token->accessToken;
        $personalAccessToken->update([
            'description'          => $data['description'] ?? null,
            'organization_id'      => $data['organization_id'] ?? $user->org_id,
            'rate_limit_per_hour'  => $data['rate_limit_per_hour'] ?? 1000,
            'rate_limit_per_day'   => $data['rate_limit_per_day'] ?? 24000,
            'rate_limit_per_month' => $data['rate_limit_per_month'] ?? 720000,
            'allowed_ips'          => $data['allowed_ips'] ?? null,
            'allowed_origins'      => $data['allowed_origins'] ?? null,
            'expires_at'           => $data['expires_at'] ?? null,
            'key_type'             => $data['key_type'] ?? 'api',
            'metadata'             => $data['metadata'] ?? null,
        ]);

        return [
            'token'      => $token->plainTextToken,
            'token_id'   => $personalAccessToken->id,
            'name'       => $tokenName,
            'expires_at' => $personalAccessToken->expires_at,
        ];
    }

    public function deleteApiKey(int $tokenId): bool {
        $token = PersonalAccessToken::find($tokenId);

        if (! $token) {
            return false;
        }

        return $token->delete();
    }

    public function getApiKeys(?int $organizationId = null, ?int $userId = null): Collection {
        $query = PersonalAccessToken::with(['tokenable'])
            ->orderBy('created_at', 'desc');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($userId) {
            $query->where('tokenable_type', User::class)
                ->where('tokenable_id', $userId);
        }

        return $query->get();
    }

    public function getApiKeysOverview(?int $organizationId = null): array {
        $query = PersonalAccessToken::query();

        // Apply organization filter only if organizationId is not null
        // This allows super admins to get data for all organizations
        if ($organizationId !== null) {
            if ($organizationId === 0) {
                $query->whereNull('organization_id');
            } else {
                $query->where('organization_id', $organizationId);
            }
        }

        $stats = $query
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN last_used_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as used_today,
                SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 ELSE 0 END) as expired
            ')
            ->first();

        return [
            'total'      => $stats->total ?? 0,
            'active'     => $stats->active ?? 0,
            'used_today' => $stats->used_today ?? 0,
            'expired'    => $stats->expired ?? 0,
        ];
    }

    public function getApiKeyUsageStats(int $tokenId, array $options = []): array {
        $startDate = $options['start_date'] ?? now()->subDays(30);
        $endDate   = $options['end_date'] ?? now();
        $groupBy   = $options['group_by'] ?? 'day';

        $baseQuery = static fn () => ApiKeyUsage::where('token_id', $tokenId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Total requests
        $totalRequests = $baseQuery()->count();

        // Requests by status code
        $statusCodeStats = $baseQuery()->selectRaw('response_status, COUNT(*) as count')
            ->groupBy('response_status')
            ->pluck('count', 'response_status')
            ->toArray();

        // Requests over time
        $timeFormat = match ($groupBy) {
            'hour'  => '%Y-%m-%d %H:00:00',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $requestsOverTime = $baseQuery()->selectRaw("DATE_FORMAT(created_at, '{$timeFormat}') as period, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period')
            ->toArray();

        // Top endpoints
        $topEndpoints = $baseQuery()->selectRaw('endpoint, COUNT(*) as count')
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'endpoint')
            ->toArray();

        // Average response time
        $avgResponseTime = $baseQuery()->avg('response_time');

        return [
            'total_requests'        => $totalRequests,
            'status_code_stats'     => $statusCodeStats,
            'requests_over_time'    => $requestsOverTime,
            'top_endpoints'         => $topEndpoints,
            'average_response_time' => round($avgResponseTime ?? 0, 2),
            'success_rate'          => $totalRequests > 0 ? round((($statusCodeStats[200] ?? 0) / $totalRequests) * 100, 2) : 0,
        ];
    }

    public function regenerateApiKey(int $tokenId): ?array {
        $oldToken = PersonalAccessToken::find($tokenId);

        if (! $oldToken) {
            return null;
        }

        $user = $oldToken->tokenable;
        // Create new token with same settings
        $newTokenData = [
            'name'            => $oldToken->name,
            'description'     => $oldToken->description,
            'user_id'         => $user->id,
            'organization_id' => $oldToken->organization_id,
            'abilities'       => is_array($oldToken->abilities)
                ? $oldToken->abilities
                : (json_decode($oldToken->abilities, true) ?? ['*']),
            'rate_limit_per_hour'  => $oldToken->rate_limit_per_hour,
            'rate_limit_per_day'   => $oldToken->rate_limit_per_day,
            'rate_limit_per_month' => $oldToken->rate_limit_per_month,
            'allowed_ips'          => $oldToken->allowed_ips,
            'allowed_origins'      => $oldToken->allowed_origins,
            'expires_at'           => $oldToken->expires_at,
            'key_type'             => $oldToken->key_type,
            'metadata'             => $oldToken->metadata,
        ];

        $newToken = $this->createApiKey($newTokenData);

        // Revoke old token
        $oldToken->delete();

        return $newToken;
    }

    public function revokeApiKey(int $tokenId): bool {
        $token = PersonalAccessToken::find($tokenId);

        if (! $token) {
            return false;
        }

        $token->update(['is_active' => false]);

        return true;
    }

    public function updateApiKey(int $tokenId, array $data): bool {
        $token = PersonalAccessToken::find($tokenId);

        if (! $token) {
            return false;
        }

        // Don't filter out rate limit fields - we want to allow setting them to null
        $updateData = [];

        // Handle regular fields (filter out nulls)
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['allowed_ips'])) {
            $updateData['allowed_ips'] = $data['allowed_ips'];
        }
        if (isset($data['allowed_origins'])) {
            $updateData['allowed_origins'] = $data['allowed_origins'];
        }
        if (isset($data['expires_at'])) {
            $updateData['expires_at'] = $data['expires_at'];
        }
        if (isset($data['abilities'])) {
            $updateData['abilities'] = $data['abilities'];
        }
        if (isset($data['metadata'])) {
            $updateData['metadata'] = $data['metadata'];
        }

        // Handle rate limits specially - allow null values to clear limits
        if (array_key_exists('rate_limit_per_hour', $data)) {
            $updateData['rate_limit_per_hour'] = $data['rate_limit_per_hour'];
        }
        if (array_key_exists('rate_limit_per_day', $data)) {
            $updateData['rate_limit_per_day'] = $data['rate_limit_per_day'];
        }
        if (array_key_exists('rate_limit_per_month', $data)) {
            $updateData['rate_limit_per_month'] = $data['rate_limit_per_month'];
        }

        return $token->update($updateData);
    }
}
