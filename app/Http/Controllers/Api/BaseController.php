<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseController extends Controller
{
    // HTTP status codes
    protected const HTTP_OK = 200;
    protected const HTTP_CREATED = 201;
    protected const HTTP_BAD_REQUEST = 400;
    protected const HTTP_UNAUTHORIZED = 401;
    protected const HTTP_FORBIDDEN = 403;
    protected const HTTP_NOT_FOUND = 404;
    protected const HTTP_CONFLICT = 409;
    protected const HTTP_SERVER_ERROR = 500;

    // Default messages
    protected const DEFAULT_SUCCESS_MESSAGE = 'Success';
    protected const DEFAULT_ERROR_MESSAGE = 'Error';

    // Success response
    protected function successResponse(mixed $data, string $message = self::DEFAULT_SUCCESS_MESSAGE, int $statusCode = self::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    // Error response
    protected function errorResponse(string $message = self::DEFAULT_ERROR_MESSAGE, int $statusCode = self::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }

    // Resource response
    protected function resourceResponse(JsonResource $resource, string $message = self::DEFAULT_SUCCESS_MESSAGE, int $statusCode = self::HTTP_OK): JsonResponse
    {
        return $this->successResponse($resource, $message, $statusCode);
    }
}
