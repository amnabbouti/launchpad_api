<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessageGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Paginate a query builder using request parameters
     */
    protected function paginated(Builder $query, Request $request)
    {
        $perPage = (int) $request->get('per_page', 40);
        $perPage = min($perPage, 100);

        return $query->paginate($perPage);
    }

    /**
     * Return a successful response with a proper translation
     */
    protected function successResponse($data = null, string $message = 'succ.default', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => MessageGeneratorService::generate($message),
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error response with a proper translation
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => MessageGeneratorService::generate($message),
            'data' => null,
        ], $status);
    }
}
