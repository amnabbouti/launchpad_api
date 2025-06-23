<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ThreatDetectionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'summary' => [
                'total_requests' => $this->resource['summary']['total_requests'],
                'unique_ips' => $this->resource['summary']['unique_ips'],
                'failed_requests' => $this->resource['summary']['failed_requests'],
                'auth_failures' => $this->resource['summary']['auth_failures'],
                'rate_limited' => $this->resource['summary']['rate_limited'],
                'failure_rate' => $this->resource['summary']['failure_rate'],
                'threat_level' => $this->resource['summary']['threat_level'],
                'avg_response_time' => $this->resource['summary']['avg_response_time'],
            ],
            'high_risk_ips' => collect($this->resource['high_risk_ips'])->map(function ($ip) {
                return [
                    'ip_address' => $ip['ip_address'],
                    'total_requests' => $ip['total_requests'],
                    'failed_requests' => $ip['failed_requests'],
                    'auth_failures' => $ip['auth_failures'],
                    'failure_rate' => $ip['failure_rate'],
                    'unique_endpoints' => $ip['unique_endpoints'],
                    'unique_keys' => $ip['unique_keys'],
                    'risk_score' => $ip['risk_score'],
                    'risk_level' => $ip['risk_level'],
                    'first_seen' => $ip['first_seen'],
                    'last_seen' => $ip['last_seen'],
                    'avg_response_time' => $ip['avg_response_time'],
                ];
            }),
            'attack_patterns' => collect(array_merge(
                $this->resource['attack_patterns']['brute_force'] ?? [],
                $this->resource['attack_patterns']['endpoint_scanning'] ?? []
            ))->map(function ($pattern) {
                return [
                    'type' => $pattern['type'],
                    'ip_address' => $pattern['ip_address'],
                    'count' => $pattern['type'] === 'brute_force' ? $pattern['attempts'] : $pattern['total_requests'],
                    'description' => $pattern['type'] === 'brute_force'
                        ? "Multiple authentication failures detected from this IP"
                        : "Scanning multiple endpoints for vulnerabilities",
                    'timeframe' => 'Last 24 hours',
                    'first_seen' => now()->subHours(24)->toISOString(),
                    'last_seen' => now()->toISOString(),
                ];
            }),
            'security_alerts' => collect($this->resource['security_alerts'])->map(function ($alert) {
                return [
                    'type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'title' => $alert['title'] ?? '',
                    'description' => $alert['description'],
                    'ip_address' => $alert['ip_address'] ?? null,
                    'count' => $alert['auth_failures'] ?? $alert['rate_limited_requests'] ?? 1,
                    'created_at' => $alert['created_at'],
                    'details' => $alert['details'] ?? null,
                ];
            }),
            'api_key_insights' => $this->resource['api_key_insights'],
            'metadata' => [
                'time_range_hours' => 24,
                'generated_at' => now(),
                'total_alerts' => count($this->resource['security_alerts']),
                'critical_alerts' => collect($this->resource['security_alerts'])->where('severity', 'critical')->count(),
                'high_alerts' => collect($this->resource['security_alerts'])->where('severity', 'high')->count(),
            ],
            'user_behavior' => [
                'hourly_pattern' => $this->resource['user_behavior']['hourly_pattern'] ?? [],
                'top_endpoints' => $this->resource['user_behavior']['top_endpoints'] ?? [],
                'user_demographics' => $this->resource['user_behavior']['user_demographics'] ?? [],
                'session_analysis' => $this->resource['user_behavior']['session_analysis'] ?? [],
                'token_type_distribution' => $this->resource['user_behavior']['token_type_distribution'] ?? [],
            ],
            'geographic_analysis' => [
                'top_ips' => $this->resource['geographic_analysis']['top_ips'] ?? [],
                'total_unique_ips' => $this->resource['geographic_analysis']['total_unique_ips'] ?? 0,
            ],
        ];
    }
}
