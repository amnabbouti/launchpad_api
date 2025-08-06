<?php

namespace App\Http\Controllers\Api\Auth;

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

class AuthController extends BaseController
{
    /**
     * Login user and return access token with session key
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->errorResponse(
                ErrorMessages::INVALID_CREDENTIALS,
                HttpStatus::HTTP_UNAUTHORIZED
            );
        }

        $user = Auth::user();

        // Check if user already has a stored token
        $userToken = \App\Models\UserToken::where('user_id', $user->id)
            ->where('token_type', 'mobile')
            ->first();

        if ($userToken) {
            $plainTextToken = $userToken->plain_text_token;
        } else {
            $tokenObject = $user->createToken('mobile-app');
            $plainTextToken = $tokenObject->plainTextToken;

            \App\Models\UserToken::create([
                'user_id' => $user->id,
                'token_type' => 'mobile',
                'plain_text_token' => $plainTextToken,
                'personal_access_token_id' => $tokenObject->accessToken->id,
                'is_active' => true,
            ]);
        }

        // Generate unique session key and token version
        $sessionKey = bin2hex(random_bytes(32));
        $tokenVersion = time();

        // Generate device fingerprint
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
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $sessionKey = $request->header('X-Session-Key');

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
     * Generate device fingerprint
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
