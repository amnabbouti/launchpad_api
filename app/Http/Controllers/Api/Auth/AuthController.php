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

        // Create a new token for the user
        $token = $user->createToken('expo-app')->plainTextToken;

        // Token encryption
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
            'Logged out successfully'
        );
    }
}
