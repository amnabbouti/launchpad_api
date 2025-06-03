<?php

namespace App\Http\Controllers\Api;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseController extends Controller
{
    /** Default success message. */
    protected const DEFAULT_SUCCESS_MESSAGE = 'Success';

    /**
     * Return a success response.
     */
    protected function successResponse(
        mixed $data,
        string $message = self::DEFAULT_SUCCESS_MESSAGE,
        int $statusCode = HttpStatus::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(
        string $message = ErrorMessages::SERVER_ERROR,
        int $statusCode = HttpStatus::HTTP_BAD_REQUEST,
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }

    /**
     * Return a resource collection response.
     */
    protected function resourceResponse(
        JsonResource $resource,
        string $message = self::DEFAULT_SUCCESS_MESSAGE,
        int $statusCode = HttpStatus::HTTP_OK,
    ): JsonResponse {
        return $this->successResponse($resource, $message, $statusCode);
    }
}
