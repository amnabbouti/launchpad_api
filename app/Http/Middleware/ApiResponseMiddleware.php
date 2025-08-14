<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Services\MessageGeneratorService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiResponseMiddleware {
    public static function createResponse($data, $resourceType, $resourceData = []) {
        $message = MessageGeneratorService::generateCreateMessage($resourceType, $resourceData);

        return self::jsonResponse($message, $data, 201);
    }

    public static function deleteResponse($resourceType, $resourceData = []) {
        $message = MessageGeneratorService::generateDeleteMessage($resourceType, $resourceData);

        return self::jsonResponse($message, null, 204);
    }

    public static function listResponse($data, $resourceType, $count = null) {
        $response = $data->toResponse(request());
        $content  = $response->getData(true);
        $count    = $count ?? $content['meta']['total'];
        $message  = MessageGeneratorService::generateListMessage($resourceType, $count);

        $responseData = [
            'status'  => 'success',
            'message' => $message,
            'data'    => $content['data'],
        ];

        if (isset($content['links'])) {
            $responseData['links'] = $content['links'];
        }

        if (isset($content['meta'])) {
            $responseData['meta'] = $content['meta'];
        }

        return response()->json($responseData);
    }

    public static function showResponse($data, $resourceType, $resourceData = []) {
        $message = MessageGeneratorService::generateShowMessage($resourceType, $resourceData);

        return self::jsonResponse($message, $data);
    }

    public static function success($data, $message = 'succ.default', $status = 200) {
        return self::jsonResponse(MessageGeneratorService::generate($message), $data, $status);
    }

    public static function updateResponse($data, $resourceType, $resourceData = []) {
        $message = MessageGeneratorService::generateUpdateMessage($resourceType, $resourceData);

        return self::jsonResponse($message, $data);
    }

    public function handle(Request $request, Closure $next) {
        $response = $next($request);

        if (! $request->is('api/*')) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $content = $response->getData(true);
            if (isset($content['status'])) {
                return $response;
            }
        }

        return $response;
    }

    private static function jsonResponse(string $message, $data, int $status = 200): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
}
