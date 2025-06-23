<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiKeyController extends BaseController
{
    protected ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Display a listing of API keys
     */
    public function index(Request $request): JsonResponse
    {
        // Super admins automatically get all organizations, regular users get their org only
        $organizationId = $request->user()->isSuperAdmin() ? null : $request->user()->org_id;
        $userId = $request->query('user_id');

        $apiKeys = $this->apiKeyService->getApiKeys($organizationId, $userId);

        $formattedKeys = $apiKeys->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'description' => $token->description,
                'token_preview' => substr($token->token, 0, 8) . '...' . substr($token->token, -4),
                'user' => $token->tokenable ? [
                    'id' => $token->tokenable->public_id,
                    'name' => $token->tokenable->first_name . ' ' . $token->tokenable->last_name,
                    'email' => $token->tokenable->email,
                ] : null,
                'abilities' => $token->abilities,
                'rate_limits' => [
                    'hour' => $token->rate_limit_per_hour,
                    'day' => $token->rate_limit_per_day,
                    'month' => $token->rate_limit_per_month,
                ],
                'restrictions' => [
                    'allowed_ips' => $token->allowed_ips,
                    'allowed_origins' => $token->allowed_origins,
                ],
                'is_active' => $token->is_active,
                'key_type' => $token->key_type,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ];
        });

        return $this->successResponse($formattedKeys);
    }

    /**
     * Store a newly created API key
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'user_id' => 'nullable|exists:users,id',
            'organization_id' => 'nullable|exists:organizations,id', // Allow super admins to specify org
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'rate_limit_per_hour' => 'nullable|integer|min:1|max:10000',
            'rate_limit_per_day' => 'nullable|integer|min:1|max:100000',
            'rate_limit_per_month' => 'nullable|integer|min:1|max:1000000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'allowed_origins' => 'nullable|array',
            'allowed_origins.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
            'key_type' => ['nullable', Rule::in(['api', 'webhook', 'integration'])],
            'metadata' => 'nullable|array',
        ]);

        if ($request->user()->isSuperAdmin()) {
            $validated['organization_id'] = $validated['organization_id'] ?? null;
        } else {
            $validated['organization_id'] = $request->user()->org_id;
        }

        if (!isset($validated['user_id'])) {
            $validated['user_id'] = $request->user()->id;
        }

        $result = $this->apiKeyService->createApiKey($validated);

        return $this->successResponse([
            'message' => 'API key created successfully',
            'api_key' => $result,
        ], 201);
    }

    /**
     * Display the specified API key
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $organizationId = $request->user()->isSuperAdmin() ? null : $request->user()->org_id;

        $apiKeys = $this->apiKeyService->getApiKeys($organizationId);
        $token = $apiKeys->firstWhere('id', $id);

        if (!$token) {
            return $this->errorResponse('API key not found', 404);
        }

        return $this->successResponse([
            'id' => $token->id,
            'name' => $token->name,
            'description' => $token->description,
            'user' => $token->tokenable ? [
                'id' => $token->tokenable->public_id,
                'name' => $token->tokenable->first_name . ' ' . $token->tokenable->last_name,
                'email' => $token->tokenable->email,
            ] : null,
            'abilities' => $token->abilities,
            'rate_limits' => [
                'hour' => $token->rate_limit_per_hour,
                'day' => $token->rate_limit_per_day,
                'month' => $token->rate_limit_per_month,
            ],
            'restrictions' => [
                'allowed_ips' => $token->allowed_ips,
                'allowed_origins' => $token->allowed_origins,
            ],
            'is_active' => $token->is_active,
            'key_type' => $token->key_type,
            'metadata' => $token->metadata,
            'last_used_at' => $token->last_used_at,
            'expires_at' => $token->expires_at,
            'created_at' => $token->created_at,
        ]);
    }

    /**
     * Update the specified API key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'rate_limit_per_hour' => 'nullable|integer|min:1|max:10000',
            'rate_limit_per_day' => 'nullable|integer|min:1|max:100000',
            'rate_limit_per_month' => 'nullable|integer|min:1|max:1000000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'allowed_origins' => 'nullable|array',
            'allowed_origins.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ]);

        $success = $this->apiKeyService->updateApiKey($id, $validated);

        if (!$success) {
            return $this->errorResponse('API key not found', 404);
        }

        return $this->successResponse(['message' => 'API key updated successfully']);
    }

    /**
     * Remove the specified API key
     */
    public function destroy(int $id): JsonResponse
    {
        $success = $this->apiKeyService->deleteApiKey($id);

        if (!$success) {
            return $this->errorResponse('API key not found', 404);
        }

        return $this->successResponse(['message' => 'API key deleted successfully']);
    }

    /**
     * Revoke the specified API key
     */
    public function revoke(int $id): JsonResponse
    {
        $success = $this->apiKeyService->revokeApiKey($id);

        if (!$success) {
            return $this->errorResponse('API key not found', 404);
        }

        return $this->successResponse(['message' => 'API key revoked successfully']);
    }

    /**
     * Regenerate the specified API key
     */
    public function regenerate(int $id): JsonResponse
    {
        $result = $this->apiKeyService->regenerateApiKey($id);

        if (!$result) {
            return $this->errorResponse('API key not found', 404);
        }

        return $this->successResponse([
            'message' => 'API key regenerated successfully',
            'api_key' => $result,
        ]);
    }

    /**
     * Get usage statistics for the specified API key
     */
    public function usage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:hour,day,month',
        ]);

        $stats = $this->apiKeyService->getApiKeyUsageStats($id, $validated);

        return $this->successResponse($stats);
    }

    /**
     * Get basic API keys overview statistics
     */
    public function overview(Request $request): JsonResponse
    {
        $organizationId = $request->user()->isSuperAdmin() ? null : $request->user()->org_id;
        $overview = $this->apiKeyService->getApiKeysOverview($organizationId);

        return $this->successResponse($overview);
    }
}
