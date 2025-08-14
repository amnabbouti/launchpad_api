<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Models\ApiKeyRateLimit;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RateLimitMiddleware {
    public function handle(Request $request, Closure $next, string $windowType = 'hour'): Response {
        $token = $request->get('api_token');

        if (! $token instanceof PersonalAccessToken) {
            return response()->json([
                'error'   => 'Authentication required',
                'message' => 'This middleware requires authentication',
            ], 401);
        }

        // Check rate limit
        $rateLimitInfo = ApiKeyRateLimit::checkRateLimit($token, $windowType);

        if ($rateLimitInfo['exceeded']) {
            return response()->json([
                'error'      => 'Rate limit exceeded',
                'message'    => "You have exceeded the rate limit of {$rateLimitInfo['limit']} requests per {$windowType}",
                'rate_limit' => [
                    'current'    => $rateLimitInfo['current'],
                    'limit'      => $rateLimitInfo['limit'],
                    'reset_time' => $rateLimitInfo['reset_time']->toISOString(),
                ],
            ], 429);
        }

        $response = $next($request);

        // Increment usage count after successful request
        if ($response->getStatusCode() < 400) {
            ApiKeyRateLimit::incrementUsage($token, $windowType);
        }

        // Add rate limit headers to response
        $response->headers->set('X-RateLimit-Limit', $rateLimitInfo['limit']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $rateLimitInfo['limit'] - $rateLimitInfo['current'] - 1));
        $response->headers->set('X-RateLimit-Reset', $rateLimitInfo['reset_time']->timestamp);

        return $response;
    }
}
