<?php

namespace App\Http\Controllers\Api\Admin;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminAuthController extends BaseController
{
    /**
     * Admin login - Only super admin users can login to the dashboard
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // TEMPORARY BYPASS for session key check for testing from Postman
        // Remove or comment this block after testing!
        /*
        $sessionKey = $request->header('X-Session-Key');
        if (!$sessionKey) {
            return $this->errorResponse(
                'Session key required. Make sure to include X-Session-Key header.',
                HttpStatus::HTTP_UNAUTHORIZED
            );
        }
        */

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->errorResponse(
                ErrorMessages::INVALID_CREDENTIALS,
                HttpStatus::HTTP_UNAUTHORIZED
            );
        }

        $user = Auth::user();

        // Restrict access to super admin users
        if (!$user->isSuperAdmin()) {
            return $this->errorResponse(
                'Access denied. Only super administrators can access this dashboard.',
                HttpStatus::HTTP_FORBIDDEN
            );
        }

        // Check if admin user already has a stored token
        $userToken = \App\Models\UserToken::where('user_id', $user->id)
            ->where('token_type', 'admin')
            ->first();
        if ($userToken) {
            // Reuse existing token
            $plainTextToken = $userToken->plain_text_token;
        } else {
            // First time login then create new token and store it
            $tokenObject = $user->createToken('admin-dashboard');
            $plainTextToken = $tokenObject->plainTextToken;

            // Store the token
            \App\Models\UserToken::create([
                'user_id' => $user->id,
                'token_type' => 'admin',
                'plain_text_token' => $plainTextToken,
                'personal_access_token_id' => $tokenObject->accessToken->id,
                'is_active' => true,
            ]);
        }

        // Generate unique session key and token version
        $sessionKey = bin2hex(random_bytes(32));
        $tokenVersion = time();

        // Generate device fingerprint for this admin login session
        $deviceFingerprint = $this->generateDeviceFingerprint($request);

        // Store session data in cache with device fingerprint
        $sessionData = [
            'user_id' => $user->id,
            'token_version' => $tokenVersion,
            'device_fingerprint' => $deviceFingerprint,
            'created_at' => now(),
            'last_activity' => now(),
        ];
        Cache::put("session_key_{$sessionKey}", $sessionData, now()->addHours(24));

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $plainTextToken,
            'session_key' => $sessionKey,
            'token_type' => 'Bearer',
        ], SuccessMessages::LOGIN_SUCCESS);
    }

    /**
     * Get authenticated admin user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse([
            'user' => new UserResource($request->user()),
        ]);
    }
    /**
     * Admin logout (clear session but keep token persistent)
     */
    public function logout(Request $request): JsonResponse
    {
        // Get session key from header to clean up cache (optional for logout)
        $sessionKey = $request->header('X-Session-Key');

        // DON'T revoke the admin token - keep it persistent for tracking
        // Only clear session cache        // Clean up session cache if session key is provided
        if ($sessionKey) {
            Cache::forget("session_key_{$sessionKey}");
            Cache::forget("requests_{$sessionKey}");
        }
        return $this->successResponse(
            null,
            SuccessMessages::LOGOUT_SUCCESS
        );
    }
    /**
     * Generate a comprehensive device fingerprint for enhanced security
     * This creates a unique identifier based on various client characteristics
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
