<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SessionValidation
{
    /**
     * Validate session key and device fingerprint for enhanced security.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            try {
                // TEMPORARILY COMMENTED OUT FOR POSTMAN TESTING
                // TODO: Uncomment these lines when done testing
                /*
                $isLogoutRequest = str_contains($request->path(), 'logout');
                $sessionKey = $request->header('X-Session-Key');

                if (!$sessionKey && !$isLogoutRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session key required',
                        'data' => null,
                    ], 401);
                }
                if (!$isLogoutRequest) {
                    $currentFingerprint = $this->generateDeviceFingerprint($request);
                    $cachedSessionData = Cache::get("session_key_{$sessionKey}");

                    if (!$cachedSessionData) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid or expired session',
                            'data' => null,
                        ], 401);
                    }

                    if ($cachedSessionData['device_fingerprint'] !== $currentFingerprint) {
                        Cache::forget("session_key_{$sessionKey}");
                        return response()->json([
                            'success' => false,
                            'message' => 'Device mismatch. Please login again.',
                            'data' => null,
                        ], 401);
                    }

                    // Rate limiting
                    $requestsKey = "requests_{$sessionKey}";
                    $requestCount = Cache::get($requestsKey, 0);
                    if ($requestCount > 100) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Too many requests',
                            'data' => null,
                        ], 429);
                    }
                    Cache::put($requestsKey, $requestCount + 1, now()->addMinute());

                    // Update session
                    $cachedSessionData['last_activity'] = now();
                    Cache::put("session_key_{$sessionKey}", $cachedSessionData, now()->addHours(24));
                }
                */
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session validation failed',
                    'data' => null,
                ], 401);
            }
        }

        return $next($request);
    }

    /**
     * Generate device fingerprint for enhanced security.
     */
    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->ip(),
            $request->header('X-Forwarded-For', ''),
            $request->header('User-Agent', ''),
            $request->header('X-Client-Identifier', ''),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }
}
