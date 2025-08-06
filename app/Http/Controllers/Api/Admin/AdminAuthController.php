<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\UserToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminAuthController extends BaseController
{
    /**
     * Admin login - Only super admin users can access the dashboard
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $sessionKey = $request->header('X-Session-Key');
        if (! $sessionKey) {
            return $this->errorResponse(
                ErrorMessages::SESSION_KEY_REQUIRED,
                HttpStatus::HTTP_UNAUTHORIZED
            );
        }

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->errorResponse(
                ErrorMessages::INVALID_CREDENTIALS,
                HttpStatus::HTTP_UNAUTHORIZED
            );
        }

        $user = Auth::user();

        if (! $user->isSuperAdmin()) {
            return $this->errorResponse(
                ErrorMessages::ADMIN_ACCESS_DENIED,
                HttpStatus::HTTP_FORBIDDEN
            );
        }

        $userToken = UserToken::where('user_id', $user->id)
            ->where('token_type', 'admin')
            ->first();
        if ($userToken) {
            $plainTextToken = $userToken->plain_text_token;
        } else {
            $tokenObject = $user->createToken('admin-dashboard');
            $plainTextToken = $tokenObject->plainTextToken;

            UserToken::create([
                'user_id' => $user->id,
                'token_type' => 'admin',
                'plain_text_token' => $plainTextToken,
                'personal_access_token_id' => $tokenObject->accessToken->id,
                'is_active' => true,
            ]);
        }

        $sessionKey = bin2hex(random_bytes(32));
        $tokenVersion = time();
        $deviceFingerprint = $this->generateDeviceFingerprint($request);

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
     * Get an authenticated admin user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Admin logout - Clears session cache but keeps token persistent
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
     * Generate device fingerprint for enhanced security
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
