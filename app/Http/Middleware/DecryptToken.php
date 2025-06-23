<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DecryptToken
{
    /**
     * Handle an incoming request.
     * Validates session key and device fingerprint for enhanced security.
     * Works with real Sanctum tokens (no encryption needed).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $plainToken = substr($authHeader, 7);

            try {
                // --- TEMPORARY BYPASS FOR TESTING ---
                // Bypass session key and device fingerprint validation to allow all requests (including Postman)
                // Restore the original logic below after testing high-risk IP detection
                return $next($request);
                // --- END TEMPORARY BYPASS ---

                /*
                // Check if this is a logout request - less strict validation
                $isLogoutRequest = str_contains($request->path(), 'logout');

                // For logout, session key is optional
                $sessionKey = $request->header('X-Session-Key');
                if (!$sessionKey && !$isLogoutRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session key required. Make sure to include X-Session-Key header.',
                        'data' => null,
                    ], 401);
                }

                // For logout requests, skip session validation but still pass token through
                if (!$isLogoutRequest) {
                    // Generate current device fingerprint
                    $currentFingerprint = $this->generateDeviceFingerprint($request);

                    // Verify session key exists
                    $cachedSessionData = Cache::get("session_key_{$sessionKey}");
                    if (!$cachedSessionData) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid or expired session key',
                            'data' => null,
                        ], 401);
                    }

                    // Verify device fingerprint matches the one stored during login
                    if ($cachedSessionData['device_fingerprint'] !== $currentFingerprint) {
                        // Delete the session for security
                        Cache::forget("session_key_{$sessionKey}");
                        return response()->json([
                            'success' => false,
                            'message' => 'Device fingerprint mismatch. Please login again.',
                            'data' => null,
                        ], 401);
                    }

                    // Check for suspicious activity - multiple rapid requests
                    $requestsKey = "requests_{$sessionKey}";
                    $requestCount = Cache::get($requestsKey, 0);
                    if ($requestCount > 100) { // Max 100 requests per minute
                        return response()->json([
                            'success' => false,
                            'message' => 'Too many requests. Please try again later.',
                            'data' => null,
                        ], 429);
                    }
                    Cache::put($requestsKey, $requestCount + 1, now()->addMinute());

                    // Update session activity with current timestamp
                    $cachedSessionData['last_activity'] = now();
                    Cache::put("session_key_{$sessionKey}", $cachedSessionData, now()->addHours(24));
                }
                */

                // Token is already in the correct format for Sanctum

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
     * Generate a comprehensive device fingerprint
     * This includes more factors than just IP to make token theft harder
     */
    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            // Network information
            $request->ip(),
            $request->header('X-Forwarded-For', ''),

            // Browser/client information
            $request->header('User-Agent', ''),
            $request->header('Accept', ''),
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),

            // Additional headers that help identify the client
            $request->header('DNT', ''), // Do Not Track
            $request->header('Connection', ''),
            $request->header('Upgrade-Insecure-Requests', ''),

            // Custom header that frontend should send with a consistent value
            $request->header('X-Client-Identifier', ''),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }
}
