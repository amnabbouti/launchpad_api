<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\ApiKeyUsage;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

use function array_slice;
use function is_string;

class ThreatDetectionService {
    public function getThreatOverview(?int $organizationId = null): array {
        $timeRange = 24;

        return [
            'summary'             => $this->getThreatSummary($organizationId, $timeRange),
            'high_risk_ips'       => $this->getHighRiskIPs($organizationId, $timeRange),
            'attack_patterns'     => $this->getAttackPatterns($organizationId, $timeRange),
            'security_alerts'     => $this->getSecurityAlerts($organizationId, $timeRange),
            'api_key_insights'    => $this->getApiKeyInsights($organizationId, $timeRange),
            'user_behavior'       => $this->getUserBehaviorAnalysis($organizationId, $timeRange),
            'geographic_analysis' => $this->getGeographicAnalysis($organizationId, $timeRange),
        ];
    }

    /**
     * Build base query with organization filtering
     */
    private function buildBaseQuery(?int $organizationId, int $hours): Builder {
        $query = ApiKeyUsage::query()
            ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
            ->where('api_key_usage.created_at', '>=', now()->subHours($hours));

        // Apply organization filter only if organizationId is not null
        if ($organizationId !== null) {
            if ($organizationId === 0) {
                $query->whereNull('personal_access_tokens.organization_id');
            } else {
                $query->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        return $query;
    }

    /**
     * Calculate risk score for IP address
     */
    private function calculateRiskScore(float $failureRate, int $authFailures, int $totalRequests, int $uniqueEndpoints): int {
        $score = 0;
        // Failure rate contribution (0-40 points)
        $score += min(40, $failureRate * 0.8);
        // Auth failures contribution (0-30 points)
        $score += min(30, $authFailures * 0.3);
        // Volume contribution (0-20 points)
        $score += min(20, $totalRequests * 0.02);
        // Scanning behavior (0-10 points)
        $score += min(10, max(0, $uniqueEndpoints - 10) * 0.5);

        return min(100, round($score));
    }

    /**
     * Calculate overall threat level
     */
    private function calculateThreatLevel(float $failureRate, int $authFailures, int $rateLimited): string {
        if ($failureRate > 50 || $authFailures > 100 || $rateLimited > 200) {
            return 'critical';
        }
        if ($failureRate > 30 || $authFailures > 50 || $rateLimited > 100) {
            return 'high';
        }
        if ($failureRate > 15 || $authFailures > 20 || $rateLimited > 50) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Extract token details from the latest usage record
     */
    private function extractTokenDetailsFromUsage(int $tokenId): array {
        $latestUsage = ApiKeyUsage::where('token_id', $tokenId)
            ->whereNotNull('request_data')
            ->orderByDesc('created_at')
            ->first();

        if (! $latestUsage || ! $latestUsage->request_data) {
            return [];
        }

        $requestData = is_string($latestUsage->request_data)
            ? json_decode($latestUsage->request_data, true)
            : $latestUsage->request_data;

        if (! isset($requestData['api_token'])) {
            return [];
        }

        $tokenData = $requestData['api_token'];

        return [
            'name'            => $tokenData['name'] ?? 'Unknown',
            'key_type'        => $tokenData['key_type'] ?? 'api',
            'organization_id' => $tokenData['organization_id'] ?? null,
            'rate_limits'     => [
                'per_hour'  => $tokenData['rate_limit_per_hour'] ?? null,
                'per_day'   => $tokenData['rate_limit_per_day'] ?? null,
                'per_month' => $tokenData['rate_limit_per_month'] ?? null,
            ],
            'allowed_ips'     => $tokenData['allowed_ips'] ?? null,
            'allowed_origins' => $tokenData['allowed_origins'] ?? null,
            'is_active'       => $tokenData['is_active'] ?? true,
            'expires_at'      => $tokenData['expires_at'] ?? null,
        ];
    }

    /**
     * Get extra details for a suspicious IP for alert context
     */
    private function getAlertDetailsForIp(string $ip, ?int $organizationId, int $hours): array {
        // Get recent usage for this IP
        $query = ApiKeyUsage::query()
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subHours($hours));
        if ($organizationId !== null) {
            $query->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id');
            if ($organizationId === 0) {
                $query->whereNull('personal_access_tokens.organization_id');
            } else {
                $query->where('personal_access_tokens.organization_id', $organizationId);
            }
        }
        $usages = $query->orderByDesc('created_at')->limit(20)->get();

        // User info (from the first token found)
        $token = $usages->first() ? $usages->first()->token : null;
        $user  = null;
        if ($token && method_exists($token, 'tokenable')) {
            $user = $token->tokenable;
        }
        $userInfo = $user ? [
            'user_id' => $user->id ?? null,
            'email'   => $user->email ?? null,
            'name'    => $user->getFullNameAttribute() ?? null,
        ] : null;
        $tokenInfo = $token ? [
            'token_id'        => $token->id,
            'description'     => $token->description ?? null,
            'organization_id' => $token->organization_id ?? null,
        ] : null;

        // Most common endpoints
        $endpointCounts      = $usages->groupBy('endpoint')->map->count()->sortDesc();
        $mostCommonEndpoints = $endpointCounts->keys()->take(3);

        // Recent user agent
        $recentUserAgent = $usages->first()?->user_agent;

        // Recent requests (timestamp + status)
        $recentRequests = $usages->map(static fn ($u) => [
            'timestamp' => $u->created_at,
            'status'    => $u->response_status,
            'endpoint'  => $u->endpoint,
        ])->take(5)->toArray();

        // HTTP methods used
        $methods = $usages->pluck('method')->unique()->values()->toArray();

        return [
            'user'                  => $userInfo,
            'token'                 => $tokenInfo,
            'most_common_endpoints' => $mostCommonEndpoints,
            'recent_user_agent'     => $recentUserAgent,
            'recent_requests'       => $recentRequests,
            'http_methods'          => $methods,
        ];
    }

    /**
     * Get API key insights from rich token data
     */
    private function getApiKeyInsights(?int $organizationId, int $hours): array {
        $query = ApiKeyUsage::query()
            ->where('api_key_usage.created_at', '>=', now()->subHours($hours))
            ->whereNotNull('api_key_usage.token_id');

        if ($organizationId !== null) {
            $query->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id');
            if ($organizationId === 0) {
                $query->whereNull('personal_access_tokens.organization_id');
            } else {
                $query->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        $keyStats = $query->selectRaw('
            COUNT(DISTINCT api_key_usage.token_id) as active_keys,
            COUNT(*) as total_requests,
            AVG(api_key_usage.response_time) as avg_response_time,
            SUM(CASE WHEN api_key_usage.response_status = 429 THEN 1 ELSE 0 END) as rate_limited_requests
        ')->first();

        $topKeys = $query->clone()
            ->selectRaw('
                api_key_usage.token_id,
                COUNT(*) as request_count,
                COUNT(DISTINCT api_key_usage.endpoint) as unique_endpoints,
                AVG(api_key_usage.response_time) as avg_response_time
            ')
            ->groupBy('api_key_usage.token_id')
            ->orderByDesc('request_count')
            ->limit(5)
            ->get()
            ->map(function ($key) {
                $tokenDetails = $this->extractTokenDetailsFromUsage($key->token_id);

                return [
                    'token_id'          => $key->token_id,
                    'name'              => $tokenDetails['name'] ?? 'Unknown',
                    'key_type'          => $tokenDetails['key_type'] ?? 'api',
                    'request_count'     => $key->request_count,
                    'unique_endpoints'  => $key->unique_endpoints,
                    'avg_response_time' => round($key->avg_response_time ?? 0, 2),
                ];
            });

        return [
            'active_keys'           => $keyStats->active_keys ?? 0,
            'total_requests'        => $keyStats->total_requests ?? 0,
            'avg_response_time'     => round($keyStats->avg_response_time ?? 0, 2),
            'rate_limited_requests' => $keyStats->rate_limited_requests ?? 0,
            'top_keys'              => $topKeys->toArray(),
        ];
    }

    /**
     * Get suspicious behavior
     */
    private function getAttackPatterns(?int $organizationId, int $hours): array {
        $query = $this->buildBaseQuery($organizationId, $hours);

        // Brute force patterns
        $bruteForce = $query->clone()
            ->selectRaw('
                api_key_usage.ip_address,
                COUNT(*) as attempts,
                SUM(CASE WHEN api_key_usage.response_status IN (401, 403) THEN 1 ELSE 0 END) as auth_failures
            ')
            ->groupBy('api_key_usage.ip_address')
            ->havingRaw('auth_failures > 20')
            ->orderByDesc('auth_failures')
            ->limit(10)
            ->get()
            ->map(static fn ($pattern) => [
                'type'          => 'brute_force',
                'ip_address'    => $pattern->ip_address,
                'attempts'      => $pattern->attempts,
                'auth_failures' => $pattern->auth_failures,
                'severity'      => $pattern->auth_failures > 50 ? 'critical' : 'high',
            ]);

        // Endpoint scanning: 5 different endpoints, 5 calls in total, all 404 or unauthorized
        $scanning = $query->clone()
            ->selectRaw('
                api_key_usage.ip_address,
                COUNT(DISTINCT api_key_usage.endpoint) as unique_endpoints,
                COUNT(*) as total_requests,
                SUM(CASE WHEN api_key_usage.response_status IN (404, 401, 403) THEN 1 ELSE 0 END) as suspicious_responses
            ')
            ->groupBy('api_key_usage.ip_address')
            ->havingRaw('unique_endpoints = 5 AND total_requests = 5 AND suspicious_responses = 5')
            ->orderByDesc('unique_endpoints')
            ->limit(10)
            ->get()
            ->map(static fn ($pattern) => [
                'type'                 => 'endpoint_scanning',
                'ip_address'           => $pattern->ip_address,
                'unique_endpoints'     => $pattern->unique_endpoints,
                'total_requests'       => $pattern->total_requests,
                'suspicious_responses' => $pattern->suspicious_responses,
                'severity'             => 'medium',
            ]);

        return [
            'brute_force'       => $bruteForce->toArray(),
            'endpoint_scanning' => $scanning->toArray(),
        ];
    }

    private function getAuthenticationFailures(?int $organizationId, int $hours): array {
        return $this->buildBaseQuery($organizationId, $hours)
            ->selectRaw('
                api_key_usage.ip_address,
                SUM(CASE WHEN api_key_usage.response_status IN (401, 403) THEN 1 ELSE 0 END) as auth_failures
            ')
            ->groupBy('api_key_usage.ip_address')
            ->havingRaw('auth_failures > 20')
            ->get()
            ->map(static fn ($ip) => [
                'ip_address'    => $ip->ip_address,
                'auth_failures' => $ip->auth_failures,
            ])
            ->toArray();
    }

    /**
     * Geographic analysis of requests
     */
    private function getGeographicAnalysis(?int $organizationId, int $hours): array {
        $query = ApiKeyUsage::query()
            ->where('api_key_usage.created_at', '>=', now()->subHours($hours));

        if ($organizationId !== null) {
            $query->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id');
            if ($organizationId === 0) {
                $query->whereNull('personal_access_tokens.organization_id');
            } else {
                $query->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        $ipAnalysis = $query->selectRaw('
            api_key_usage.ip_address,
            COUNT(*) as request_count,
            COUNT(DISTINCT api_key_usage.endpoint) as unique_endpoints,
            SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as error_count,
            MIN(api_key_usage.created_at) as first_seen,
            MAX(api_key_usage.created_at) as last_seen
        ')
            ->groupBy('api_key_usage.ip_address')
            ->orderByDesc('request_count')
            ->limit(20)
            ->get()
            ->map(fn ($ip) => [
                'ip_address'       => $ip->ip_address,
                'request_count'    => $ip->request_count,
                'unique_endpoints' => $ip->unique_endpoints,
                'error_count'      => $ip->error_count,
                'error_rate'       => $ip->request_count > 0 ? round(($ip->error_count / $ip->request_count) * 100, 2) : 0,
                'first_seen'       => $ip->first_seen,
                'last_seen'        => $ip->last_seen,
                'is_suspicious'    => $this->isSuspiciousIP($ip),
            ]);

        return [
            'top_ips'          => $ipAnalysis->toArray(),
            'total_unique_ips' => ApiKeyUsage::query()
                ->where('api_key_usage.created_at', '>=', now()->subHours($hours))
                ->when($organizationId !== null, static function ($q) use ($organizationId): void {
                    $q->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id');
                    if ($organizationId === 0) {
                        $q->whereNull('personal_access_tokens.organization_id');
                    } else {
                        $q->where('personal_access_tokens.organization_id', $organizationId);
                    }
                })
                ->distinct('api_key_usage.ip_address')
                ->count(),
        ];
    }

    /**
     * Helper methods for specific threat types
     */
    private function getHighErrorRateIPs(?int $organizationId, int $hours): array {
        return $this->buildBaseQuery($organizationId, $hours)
            ->selectRaw('
                api_key_usage.ip_address,
                COUNT(*) as total_requests,
                SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as failed_requests
            ')
            ->groupBy('api_key_usage.ip_address')
            ->havingRaw('(failed_requests / total_requests * 100) > 30')
            ->get()
            ->map(static fn ($ip) => [
                'ip_address'      => $ip->ip_address,
                'total_requests'  => $ip->total_requests,
                'failed_requests' => $ip->failed_requests,
                'failure_rate'    => round(($ip->failed_requests / $ip->total_requests) * 100, 2),
            ])
            ->toArray();
    }

    /**
     * Get IP addresses with suspicious activity
     */
    private function getHighRiskIPs(?int $organizationId, int $hours): array {
        $query = $this->buildBaseQuery($organizationId, $hours);

        return $query->selectRaw('
            api_key_usage.ip_address,
            COUNT(*) as total_requests,
            SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as failed_requests,
            SUM(CASE WHEN api_key_usage.response_status IN (401, 403) THEN 1 ELSE 0 END) as auth_failures,
            COUNT(DISTINCT api_key_usage.endpoint) as unique_endpoints,
            COUNT(DISTINCT api_key_usage.token_id) as unique_keys,
            MIN(api_key_usage.created_at) as first_seen,
            MAX(api_key_usage.created_at) as last_seen,
            AVG(api_key_usage.response_time) as avg_response_time
        ')
            ->groupBy('api_key_usage.ip_address')
            ->havingRaw('
            (failed_requests / total_requests * 100) > 30
            OR auth_failures > 10
            OR total_requests > 500
            OR unique_endpoints > 20
        ')
            ->orderByDesc('failed_requests')
            ->limit(20)
            ->get()
            ->map(function ($ip) {
                $failureRate = $ip->total_requests > 0
                    ? round(($ip->failed_requests / $ip->total_requests) * 100, 2)
                    : 0;

                $riskScore = $this->calculateRiskScore($failureRate, $ip->auth_failures, $ip->total_requests, $ip->unique_endpoints);

                return [
                    'ip_address'        => $ip->ip_address,
                    'total_requests'    => $ip->total_requests,
                    'failed_requests'   => $ip->failed_requests,
                    'auth_failures'     => $ip->auth_failures,
                    'failure_rate'      => $failureRate,
                    'unique_endpoints'  => $ip->unique_endpoints,
                    'unique_keys'       => $ip->unique_keys,
                    'risk_score'        => $riskScore,
                    'risk_level'        => $this->getRiskLevel($riskScore),
                    'first_seen'        => $ip->first_seen,
                    'last_seen'         => $ip->last_seen,
                    'avg_response_time' => round($ip->avg_response_time ?? 0, 2),
                ];
            })->toArray();
    }

    private function getRateLimitedIPs(?int $organizationId, int $hours): array {
        return $this->buildBaseQuery($organizationId, $hours)
            ->selectRaw('
                api_key_usage.ip_address,
                SUM(CASE WHEN api_key_usage.response_status = 429 THEN 1 ELSE 0 END) as rate_limited_requests
            ')
            ->groupBy('api_key_usage.ip_address')
            ->havingRaw('rate_limited_requests > 10')
            ->get()
            ->map(static fn ($ip) => [
                'ip_address'            => $ip->ip_address,
                'rate_limited_requests' => $ip->rate_limited_requests,
            ])
            ->toArray();
    }

    /**
     * Get risk level from score
     */
    private function getRiskLevel(int $score): string {
        if ($score >= 80) {
            return 'critical';
        }
        if ($score >= 60) {
            return 'high';
        }
        if ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get security alerts based on threat analysis
     */
    private function getSecurityAlerts(?int $organizationId, int $hours): array {
        $alerts = [];

        $highErrorIPs = $this->getHighErrorRateIPs($organizationId, $hours);
        foreach ($highErrorIPs as $ip) {
            $details  = $this->getAlertDetailsForIp($ip['ip_address'], $organizationId, $hours);
            $alerts[] = [
                'type'         => 'high_error_rate',
                'severity'     => $ip['failure_rate'] > 70 ? 'critical' : 'high',
                'title'        => 'High Error Rate Detected',
                'description'  => "IP {$ip['ip_address']} has {$ip['failure_rate']}% error rate ({$ip['failed_requests']}/{$ip['total_requests']} requests)",
                'ip_address'   => $ip['ip_address'],
                'failure_rate' => $ip['failure_rate'],
                'created_at'   => now(),
                'details'      => $details,
            ];
        }

        $authFailures = $this->getAuthenticationFailures($organizationId, $hours);
        foreach ($authFailures as $failure) {
            if ($failure['auth_failures'] > 30) {
                $details  = $this->getAlertDetailsForIp($failure['ip_address'], $organizationId, $hours);
                $alerts[] = [
                    'type'          => 'auth_failure_spike',
                    'severity'      => $failure['auth_failures'] > 100 ? 'critical' : 'high',
                    'title'         => 'Authentication Failure Spike',
                    'description'   => "IP {$failure['ip_address']} has {$failure['auth_failures']} authentication failures",
                    'ip_address'    => $failure['ip_address'],
                    'auth_failures' => $failure['auth_failures'],
                    'created_at'    => now(),
                    'details'       => $details,
                ];
            }
        }

        $rateLimited = $this->getRateLimitedIPs($organizationId, $hours);
        foreach ($rateLimited as $limited) {
            if ($limited['rate_limited_requests'] > 50) {
                $details  = $this->getAlertDetailsForIp($limited['ip_address'], $organizationId, $hours);
                $alerts[] = [
                    'type'                  => 'rate_limit_exceeded',
                    'severity'              => 'medium',
                    'title'                 => 'Rate Limit Exceeded',
                    'description'           => "IP {$limited['ip_address']} hit rate limits {$limited['rate_limited_requests']} times",
                    'ip_address'            => $limited['ip_address'],
                    'rate_limited_requests' => $limited['rate_limited_requests'],
                    'created_at'            => now(),
                    'details'               => $details,
                ];
            }
        }

        return array_slice($alerts, 0, 20);
    }

    /**
     * Get threat summary statistics
     */
    private function getThreatSummary(?int $organizationId, int $hours): array {
        $query = $this->buildBaseQuery($organizationId, $hours);

        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            COUNT(DISTINCT api_key_usage.ip_address) as unique_ips,
            SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as failed_requests,
            SUM(CASE WHEN api_key_usage.response_status IN (401, 403) THEN 1 ELSE 0 END) as auth_failures,
            SUM(CASE WHEN api_key_usage.response_status = 429 THEN 1 ELSE 0 END) as rate_limited,
            AVG(api_key_usage.response_time) as avg_response_time
        ')->first();

        $failureRate = $stats->total_requests > 0
            ? round(($stats->failed_requests / $stats->total_requests) * 100, 2)
            : 0;

        $threatLevel = $this->calculateThreatLevel(
            $failureRate,
            $stats->auth_failures ?? 0,
            $stats->rate_limited ?? 0,
        );

        return [
            'total_requests'    => $stats->total_requests ?? 0,
            'unique_ips'        => $stats->unique_ips ?? 0,
            'failed_requests'   => $stats->failed_requests ?? 0,
            'auth_failures'     => $stats->auth_failures ?? 0,
            'rate_limited'      => $stats->rate_limited ?? 0,
            'failure_rate'      => $failureRate,
            'threat_level'      => $threatLevel,
            'avg_response_time' => round($stats->avg_response_time ?? 0, 2),
        ];
    }

    /**
     * Analyze token type distribution and usage
     */
    private function getTokenTypeAnalysis(?int $organizationId, int $hours): array {
        $query = ApiKeyUsage::query()
            ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
            ->where('api_key_usage.created_at', '>=', now()->subHours($hours));

        if ($organizationId !== null) {
            if ($organizationId === 0) {
                $query->whereNull('personal_access_tokens.organization_id');
            } else {
                $query->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        $tokenTypes = $query->selectRaw('
            personal_access_tokens.key_type,
            COUNT(DISTINCT personal_access_tokens.id) as unique_tokens,
            COUNT(*) as total_requests,
            AVG(api_key_usage.response_time) as avg_response_time,
            SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as error_count,
            COUNT(DISTINCT api_key_usage.ip_address) as unique_ips
        ')
            ->groupBy('personal_access_tokens.key_type')
            ->orderByDesc('total_requests')
            ->get()
            ->map(static fn ($type) => [
                'key_type'          => $type->key_type,
                'unique_tokens'     => $type->unique_tokens,
                'total_requests'    => $type->total_requests,
                'avg_response_time' => round($type->avg_response_time ?? 0, 2),
                'error_count'       => $type->error_count,
                'error_rate'        => $type->total_requests > 0 ? round(($type->error_count / $type->total_requests) * 100, 2) : 0,
                'unique_ips'        => $type->unique_ips,
            ]);

        return $tokenTypes->toArray();
    }

    /**
     * Analyze user behavior patterns with rich user insights
     */
    private function getUserBehaviorAnalysis(?int $organizationId, int $hours): array {
        $query = ApiKeyUsage::query()
            ->where('api_key_usage.created_at', '>=', now()->subHours($hours));

        if ($organizationId !== null) {
            $query->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id');
            if ($organizationId === 0) {
                $query->whereNull('personal_access_tokens.organization_id');
            } else {
                $query->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        $hourlyPattern = $query->clone()
            ->selectRaw('
                HOUR(api_key_usage.created_at) as hour,
                COUNT(*) as request_count,
                COUNT(DISTINCT api_key_usage.ip_address) as unique_ips,
                COUNT(DISTINCT api_key_usage.token_id) as unique_tokens,
                AVG(api_key_usage.response_time) as avg_response_time
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(static fn ($hour) => [
                'hour'              => $hour->hour,
                'request_count'     => $hour->request_count,
                'unique_ips'        => $hour->unique_ips,
                'unique_tokens'     => $hour->unique_tokens,
                'avg_response_time' => round($hour->avg_response_time ?? 0, 2),
            ]);

        $topEndpoints = $query->clone()
            ->selectRaw('
                api_key_usage.endpoint,
                COUNT(*) as request_count,
                AVG(api_key_usage.response_time) as avg_response_time,
                SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as error_count,
                COUNT(DISTINCT api_key_usage.token_id) as unique_users
            ')
            ->groupBy('api_key_usage.endpoint')
            ->orderByDesc('request_count')
            ->limit(10)
            ->get()
            ->map(static fn ($endpoint) => [
                'endpoint'          => $endpoint->endpoint,
                'request_count'     => $endpoint->request_count,
                'avg_response_time' => round($endpoint->avg_response_time ?? 0, 2),
                'error_count'       => $endpoint->error_count,
                'error_rate'        => $endpoint->request_count > 0 ? round(($endpoint->error_count / $endpoint->request_count) * 100, 2) : 0,
                'unique_users'      => $endpoint->unique_users,
            ]);

        try {
            $userAnalysis = $this->getUserDemographicsAnalysis($organizationId, $hours);
        } catch (Exception $e) {
            $userAnalysis = [
                'geographic_distribution' => [],
                'role_distribution'       => [],
                'top_users'               => [],
            ];
        }

        try {
            $sessionAnalysis = $this->getUserSessionAnalysis($organizationId, $hours);
        } catch (Exception $e) {
            $sessionAnalysis = [
                'session_stats' => [
                    'avg_session_duration_minutes' => 0,
                    'longest_session_minutes'      => 0,
                    'total_sessions'               => 0,
                    'most_active_session'          => null,
                ],
                'token_usage_patterns' => [],
            ];
        }

        try {
            $tokenTypeAnalysis = $this->getTokenTypeAnalysis($organizationId, $hours);
        } catch (Exception $e) {
            $tokenTypeAnalysis = [
                [
                    'key_type'          => 'api',
                    'unique_tokens'     => $query->clone()->distinct('token_id')->count(),
                    'total_requests'    => $query->clone()->count(),
                    'avg_response_time' => round($query->clone()->avg('response_time') ?? 0, 2),
                    'error_count'       => $query->clone()->where('response_status', '>=', 400)->count(),
                    'error_rate'        => 0,
                    'unique_ips'        => $query->clone()->distinct('ip_address')->count(),
                ],
            ];
        }

        return [
            'hourly_pattern'          => $hourlyPattern->toArray(),
            'top_endpoints'           => $topEndpoints->toArray(),
            'user_demographics'       => $userAnalysis,
            'session_analysis'        => $sessionAnalysis,
            'token_type_distribution' => $tokenTypeAnalysis,
        ];
    }

    /**
     * Analyze user demographics and geographic distribution
     */
    private function getUserDemographicsAnalysis(?int $organizationId, int $hours): array {
        // Try to build a query with user joins, but fallback if no user_tokens exist
        $userTokensExist = DB::table('user_tokens')->whereNotNull('personal_access_token_id')->exists();

        if ($userTokensExist) {
            // Full analysis with user data
            $baseQuery = ApiKeyUsage::query()
                ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
                ->join('user_tokens', 'personal_access_tokens.id', '=', 'user_tokens.personal_access_token_id')
                ->join('users', 'user_tokens.user_id', '=', 'users.id')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->where('api_key_usage.created_at', '>=', now()->subHours($hours));
        } else {
            // Fallback: Use tokenable relationship (Laravel Sanctum default)
            $baseQuery = ApiKeyUsage::query()
                ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
                ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->where('api_key_usage.created_at', '>=', now()->subHours($hours))
                ->where('personal_access_tokens.tokenable_type', 'App\\Models\\User');
        }

        if ($organizationId !== null) {
            if ($organizationId === 0) {
                $baseQuery->whereNull('personal_access_tokens.organization_id');
            } else {
                $baseQuery->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        $geographicDistribution = $baseQuery->clone()
            ->selectRaw('
                users.country,
                users.province,
                users.city,
                COUNT(DISTINCT users.id) as user_count,
                COUNT(*) as request_count,
                AVG(api_key_usage.response_time) as avg_response_time
            ')
            ->whereNotNull('users.country')
            ->groupBy('users.country', 'users.province', 'users.city')
            ->orderByDesc('request_count')
            ->limit(10)
            ->get()
            ->map(static fn ($location) => [
                'location'          => mb_trim($location->city . ', ' . $location->province . ', ' . $location->country, ', '),
                'country'           => $location->country,
                'province'          => $location->province,
                'city'              => $location->city,
                'user_count'        => $location->user_count,
                'request_count'     => $location->request_count,
                'avg_response_time' => round($location->avg_response_time ?? 0, 2),
            ]);

        $roleDistribution = $baseQuery->clone()
            ->selectRaw('
                roles.title as role_name,
                roles.slug as role_slug,
                COUNT(DISTINCT users.id) as user_count,
                COUNT(*) as request_count,
                AVG(api_key_usage.response_time) as avg_response_time,
                SUM(CASE WHEN api_key_usage.response_status >= 400 THEN 1 ELSE 0 END) as error_count
            ')
            ->whereNotNull('roles.title')
            ->groupBy('roles.id', 'roles.title', 'roles.slug')
            ->orderByDesc('request_count')
            ->get()
            ->map(static fn ($role) => [
                'role_name'         => $role->role_name,
                'role_slug'         => $role->role_slug,
                'user_count'        => $role->user_count,
                'request_count'     => $role->request_count,
                'avg_response_time' => round($role->avg_response_time ?? 0, 2),
                'error_count'       => $role->error_count,
                'error_rate'        => $role->request_count > 0 ? round(($role->error_count / $role->request_count) * 100, 2) : 0,
            ]);

        $topUsers = $baseQuery->clone()
            ->leftJoin('organizations', 'users.org_id', '=', 'organizations.id')
            ->selectRaw('
                users.id,
                users.first_name,
                users.last_name,
                users.email,
                users.org_role,
                users.country,
                roles.title as role_title,
                organizations.id as organization_id,
                organizations.name as organization_name,
                COUNT(*) as request_count,
                COUNT(DISTINCT api_key_usage.endpoint) as unique_endpoints,
                AVG(api_key_usage.response_time) as avg_response_time,
                MIN(api_key_usage.created_at) as first_request,
                MAX(api_key_usage.created_at) as last_request
            ')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.org_role', 'users.country', 'roles.title', 'organizations.id', 'organizations.name')
            ->orderByDesc('request_count')
            ->limit(10)
            ->get()
            ->map(static fn ($user) => [
                'user_id'             => $user->id,
                'name'                => mb_trim($user->first_name . ' ' . $user->last_name),
                'email'               => $user->email,
                'org_role'            => $user->org_role,
                'system_role'         => $user->role_title,
                'country'             => $user->country,
                'request_count'       => $user->request_count,
                'unique_endpoints'    => $user->unique_endpoints,
                'avg_response_time'   => round($user->avg_response_time ?? 0, 2),
                'first_request'       => $user->first_request,
                'last_request'        => $user->last_request,
                'activity_span_hours' => $user->first_request && $user->last_request
                    ? round((strtotime($user->last_request) - strtotime($user->first_request)) / 3600, 1)
                    : 0,
                'organization' => $user->organization_id ? [
                    'id'   => $user->organization_id,
                    'name' => $user->organization_name,
                ] : null,
            ]);

        return [
            'geographic_distribution' => $geographicDistribution->toArray(),
            'role_distribution'       => $roleDistribution->toArray(),
            'top_users'               => $topUsers->toArray(),
        ];
    }

    /**
     * Analyze user session patterns
     */
    private function getUserSessionAnalysis(?int $organizationId, int $hours): array {
        $userTokensExist = DB::table('user_tokens')->whereNotNull('personal_access_token_id')->exists();

        if ($userTokensExist) {
            $baseQuery = ApiKeyUsage::query()
                ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
                ->join('user_tokens', 'personal_access_tokens.id', '=', 'user_tokens.personal_access_token_id')
                ->where('api_key_usage.created_at', '>=', now()->subHours($hours));
        } else {
            $baseQuery = ApiKeyUsage::query()
                ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
                ->where('api_key_usage.created_at', '>=', now()->subHours($hours))
                ->where('personal_access_tokens.tokenable_type', 'App\\Models\\User');
        }

        if ($organizationId !== null) {
            if ($organizationId === 0) {
                $baseQuery->whereNull('personal_access_tokens.organization_id');
            } else {
                $baseQuery->where('personal_access_tokens.organization_id', $organizationId);
            }
        }

        $userIdField = $userTokensExist ? 'user_tokens.user_id' : 'personal_access_tokens.tokenable_id';

        $sessionDurations = $baseQuery->clone()
            ->selectRaw("
                {$userIdField} as user_id,
                DATE(api_key_usage.created_at) as session_date,
                MIN(api_key_usage.created_at) as session_start,
                MAX(api_key_usage.created_at) as session_end,
                COUNT(*) as requests_in_session
            ")
            ->groupBy('user_id', 'session_date')
            ->get()
            ->map(static function ($session) {
                $duration = (strtotime($session->session_end) - strtotime($session->session_start)) / 60; // minutes

                return [
                    'user_id'             => $session->user_id,
                    'session_date'        => $session->session_date,
                    'duration_minutes'    => round($duration, 1),
                    'requests_count'      => $session->requests_in_session,
                    'requests_per_minute' => $duration > 0 ? round($session->requests_in_session / $duration, 2) : 0,
                ];
            });

        $avgSessionDuration = $sessionDurations->avg('duration_minutes');
        $longestSession     = $sessionDurations->max('duration_minutes');
        $mostActiveSession  = $sessionDurations->sortByDesc('requests_count')->first();

        if ($userTokensExist) {
            $tokenUsagePatterns = $baseQuery->clone()
                ->selectRaw('
                    user_tokens.token_type,
                    user_tokens.user_id,
                    COUNT(*) as request_count,
                    COUNT(DISTINCT DATE(api_key_usage.created_at)) as active_days,
                    AVG(api_key_usage.response_time) as avg_response_time
                ')
                ->groupBy('user_tokens.token_type', 'user_tokens.user_id')
                ->get()
                ->groupBy('token_type')
                ->map(static fn ($typeGroup, $tokenType) => [
                    'token_type'                => $tokenType,
                    'unique_users'              => $typeGroup->count(),
                    'total_requests'            => $typeGroup->sum('request_count'),
                    'avg_requests_per_user'     => round($typeGroup->avg('request_count'), 1),
                    'avg_response_time'         => round($typeGroup->avg('avg_response_time'), 2),
                    'most_active_user_requests' => $typeGroup->max('request_count'),
                ]);
        } else {
            $tokenUsagePatterns = collect([
                [
                    'token_type'                => 'api',
                    'unique_users'              => $baseQuery->clone()->distinct('personal_access_tokens.tokenable_id')->count(),
                    'total_requests'            => $baseQuery->clone()->count(),
                    'avg_requests_per_user'     => round($baseQuery->clone()->count() / max(1, $baseQuery->clone()->distinct('personal_access_tokens.tokenable_id')->count()), 1),
                    'avg_response_time'         => round($baseQuery->clone()->avg('api_key_usage.response_time') ?? 0, 2),
                    'most_active_user_requests' => $baseQuery->clone()->selectRaw('COUNT(*) as cnt')->groupBy('personal_access_tokens.tokenable_id')->orderByDesc('cnt')->value('cnt') ?? 0,
                ],
            ]);
        }

        return [
            'session_stats' => [
                'avg_session_duration_minutes' => round($avgSessionDuration ?? 0, 1),
                'longest_session_minutes'      => round($longestSession ?? 0, 1),
                'total_sessions'               => $sessionDurations->count(),
                'most_active_session'          => $mostActiveSession ? [
                    'user_id'  => $mostActiveSession['user_id'],
                    'requests' => $mostActiveSession['requests_count'],
                    'duration' => $mostActiveSession['duration_minutes'],
                ] : null,
            ],
            'token_usage_patterns' => $tokenUsagePatterns->values()->toArray(),
        ];
    }

    /**
     * Determine if an IP is suspicious
     */
    private function isSuspiciousIP($ip): bool {
        return $ip->error_count > 50
            || $ip->unique_endpoints > 20
            || ($ip->request_count > 100 && $ip->error_count / $ip->request_count > 0.3);
    }
}
