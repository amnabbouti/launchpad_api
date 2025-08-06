<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKeyUsage;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class LogApiUsageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Log usage for all API requests
        $this->logApiUsage($request, $response, $startTime);

        return $response;
    }

    private function logApiUsage(Request $request, Response $response, float $startTime): void
    {
        // Skip logging for certain routes
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Try to get the token from the request
        $token = $this->getTokenFromRequest($request);

        // Only set token_id if it's a real PersonalAccessToken
        $tokenId = ($token instanceof SanctumPersonalAccessToken) ? $token->id : null;

        // Log ALL API usage, with or without token
        $logData = [
            'token_id' => $tokenId,
            'endpoint' => $request->getPathInfo(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? '',
            'response_status' => $response->getStatusCode(),
            'response_time' => $responseTime,
            'request_data' => $this->getLoggableRequestData($request, $token),
        ];

        ApiKeyUsage::create($logData);
    }

    private function getTokenFromRequest(Request $request): SanctumPersonalAccessToken|TransientToken|null
    {
        // First try to get from API key middleware (if it was added to request)
        if ($request->has('api_token')) {
            return $request->get('api_token');
        }

        // Try to get from Sanctum auth
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();

            // Only return if it's a real PersonalAccessToken
            if ($token instanceof SanctumPersonalAccessToken && $token->id) {
                $fullToken = PersonalAccessToken::find($token->id);

                return $fullToken ?: $token;
            }

            return $token;
        }

        // Try to parse from Authorization header
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            $tokenString = substr($header, 7);

            return PersonalAccessToken::findToken($tokenString);
        }

        return null;
    }

    private function shouldSkipLogging(Request $request): bool
    {
        $skipRoutes = [
            '/up', // Health check
            '/test', // Test route
            '/get-env-key', // Environment key route
        ];

        $path = $request->getPathInfo();

        // Skip exact matches
        if (in_array($path, $skipRoutes)) {
            return true;
        }

        // Skip non-API routes (if any)
        if (! str_starts_with($path, '/api') && $path !== '/') {
            return true;
        }

        return false;
    }

    private function getLoggableRequestData(Request $request, SanctumPersonalAccessToken|TransientToken|null $token = null): array
    {
        $data = $request->except(['password', 'password_confirmation', 'token', 'api_key']);

        // Add token details if available (like your detailed logging)
        if ($token && $token instanceof PersonalAccessToken) {
            $data['api_token'] = [
                'id' => $token->id,
                'tokenable_type' => $token->tokenable_type,
                'tokenable_id' => $token->tokenable_id,
                'organization_id' => $token->organization_id,
                'name' => $token->name,
                'description' => $token->description,
                'abilities' => $token->abilities,
                'rate_limit_per_hour' => $token->rate_limit_per_hour,
                'rate_limit_per_day' => $token->rate_limit_per_day,
                'rate_limit_per_month' => $token->rate_limit_per_month,
                'allowed_ips' => $token->allowed_ips,
                'allowed_origins' => $token->allowed_origins,
                'is_active' => $token->is_active,
                'key_type' => $token->key_type,
                'metadata' => $token->metadata,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
                'updated_at' => $token->updated_at,
            ];
        }

        // Limit the size of logged data
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 2000) {
            return ['message' => 'Request data too large to log'];
        }

        return $data;
    }
}
