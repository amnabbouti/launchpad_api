<?php

namespace App\Http\Middleware;

use App\Models\ApiKeyUsage;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide a valid API key in the Authorization header'
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid'
            ], 401);
        }

        // Check if token is active
        if (!$accessToken->is_active) {
            return response()->json([
                'error' => 'API key disabled',
                'message' => 'This API key has been disabled'
            ], 401);
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'error' => 'API key expired',
                'message' => 'This API key has expired'
            ], 401);
        }

        // Check IP restrictions
        if ($accessToken->allowed_ips && !$this->isIpAllowed($request->ip(), $accessToken->allowed_ips)) {
            return response()->json([
                'error' => 'IP not allowed',
                'message' => 'Your IP address is not authorized to use this API key'
            ], 403);
        }

        // Check origin restrictions
        if ($accessToken->allowed_origins && !$this->isOriginAllowed($request->header('Origin'), $accessToken->allowed_origins)) {
            return response()->json([
                'error' => 'Origin not allowed',
                'message' => 'Your origin is not authorized to use this API key'
            ], 403);
        }

        // Update last used timestamp
        $accessToken->update(['last_used_at' => now()]);

        // Add token to request for use in other middleware/controllers
        $request->merge(['api_token' => $accessToken]);

        $response = $next($request);

        // Log API usage
        $this->logApiUsage($request, $accessToken, $response, $startTime);

        return $response;
    }

    private function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');
        
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    private function isIpAllowed(string $clientIp, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            if ($clientIp === $allowedIp || $this->ipInRange($clientIp, $allowedIp)) {
                return true;
            }
        }
        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    private function isOriginAllowed(?string $origin, array $allowedOrigins): bool
    {
        if (!$origin) {
            return false;
        }

        return in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins);
    }

    private function logApiUsage(Request $request, PersonalAccessToken $token, Response $response, float $startTime): void
    {
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        ApiKeyUsage::logUsage(
            $token,
            $request->getPathInfo(),
            $request->method(),
            $request->ip(),
            $request->userAgent() ?? '',
            $response->getStatusCode(),
            $responseTime,
            $this->getLoggableRequestData($request)
        );
    }

    private function getLoggableRequestData(Request $request): array
    {
        $data = $request->except(['password', 'password_confirmation', 'token', 'api_key']);
        
        // Limit the size of logged data
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 1000) {
            return ['message' => 'Request data too large to log'];
        }

        return $data;
    }
}
