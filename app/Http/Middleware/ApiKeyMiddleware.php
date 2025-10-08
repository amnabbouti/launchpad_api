<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Models\ApiKeyUsage;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function in_array;

final class ApiKeyMiddleware {
    public function handle(Request $request, Closure $next): Response {
        $startTime = microtime(true);

        $token = $this->getTokenFromRequest($request);

        if (! $token) {
            return response()->json([
                'error'   => 'API key required',
                'message' => 'Please provide a valid API key in the Authorization header',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json([
                'error'   => 'Invalid API key',
                'message' => 'The provided API key is invalid',
            ], 401);
        }

        if (! $accessToken->is_active) {
            return response()->json([
                'error'   => 'API key disabled',
                'message' => 'This API key has been disabled',
            ], 401);
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'error'   => 'API key expired',
                'message' => 'This API key has expired',
            ], 401);
        }

        if ($accessToken->allowed_ips && ! $this->isIpAllowed($request->ip(), $accessToken->allowed_ips)) {
            return response()->json([
                'error'   => 'IP not allowed',
                'message' => 'Your IP address is not authorized to use this API key',
            ], 403);
        }

        if ($accessToken->allowed_origins && ! $this->isOriginAllowed($request->header('Origin'), $accessToken->allowed_origins)) {
            return response()->json([
                'error'   => 'Origin not allowed',
                'message' => 'Your origin is not authorized to use this API key',
            ], 403);
        }

        $accessToken->update(['last_used_at' => now()]);

        $request->merge(['api_token' => $accessToken]);

        $response = $next($request);

        $this->logApiUsage($request, $accessToken, $response, $startTime);

        return $response;
    }

    private function getLoggableRequestData(Request $request): array {
        $data = $request->except(['password', 'password_confirmation', 'token', 'api_key']);

        $jsonData = json_encode($data);
        if (mb_strlen($jsonData) > 1000) {
            return ['message' => 'Request data too large to log'];
        }

        return $data;
    }

    private function getTokenFromRequest(Request $request): ?string {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return mb_substr($header, 7);
    }

    private function ipInRange(string $ip, string $range): bool {
        if (! str_contains($range, '/')  ) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);

        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    private function isIpAllowed(string $clientIp, array $allowedIps): bool {
        foreach ($allowedIps as $allowedIp) {
            if ($clientIp === $allowedIp || $this->ipInRange($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    private function isOriginAllowed(?string $origin, array $allowedOrigins): bool {
        if (! $origin) {
            return false;
        }

        return in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true);
    }

    private function logApiUsage(Request $request, PersonalAccessToken $token, Response $response, float $startTime): void {
        $responseTime = (microtime(true) - $startTime) * 1000;

        ApiKeyUsage::logUsage(
            $token,
            $request->getPathInfo(),
            $request->method(),
            $request->ip(),
            $request->userAgent() ?? '',
            $response->getStatusCode(),
            $responseTime,
            $this->getLoggableRequestData($request),
        );
    }
}
