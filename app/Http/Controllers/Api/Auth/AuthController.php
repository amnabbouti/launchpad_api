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
use Illuminate\Support\Facades\Crypt;

class AuthController extends BaseController
{
    /**
     * Login user and return encrypted token
     * Only super admin users can login to this dashboard
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

        // Restrict access to super admin users only
        if (!$user->isSuperAdmin()) {
            return $this->errorResponse(
                'Access denied. Only super administrators can access this dashboard.',
                HttpStatus::HTTP_FORBIDDEN
            );
        }

        // Create a new token for the user
        $token = $user->createToken('dashboard-app')->plainTextToken;

        // Encrypt the token for additional security
        $encryptedToken = Crypt::encryptString($token);

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $encryptedToken,
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
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            null,
            SuccessMessages::LOGOUT_SUCCESS
        );
    }
}
