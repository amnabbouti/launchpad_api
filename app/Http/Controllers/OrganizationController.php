<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\OrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Services\AuthorizationEngine;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends BaseController {
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    /**
     * Delete a specific Organization
     */
    public function destroy(string $id): JsonResponse {
        // Enforce authorization: only super admins or allowed roles may delete organizations
        $organization = $this->organizationService->findById($id);
        AuthorizationEngine::authorize('delete', 'organizations', $organization);

        $this->organizationService->delete($id);

        return ApiResponseMiddleware::deleteResponse('organization');
    }

    /**
     * Get active organizations
     */
    public function getActive(): JsonResponse {
        $organizations = $this->organizationService->getActive();
        $totalCount    = $organizations->count();

        return ApiResponseMiddleware::listResponse(
            OrganizationResource::collection($organizations),
            'organization',
            $totalCount,
        );
    }

    /**
     * Display Organizations
     */
    public function index(Request $request): JsonResponse {
        $filters            = $this->organizationService->processRequestParams($request->query());
        $organizationsQuery = $this->organizationService->getFiltered($filters);
        $totalCount         = $organizationsQuery->count();

        $organizations = $this->paginated($organizationsQuery, $request);

        return ApiResponseMiddleware::listResponse(
            OrganizationResource::collection($organizations),
            'organization',
            $totalCount,
        );
    }

    /**
     * Display a specific Organization
     */
    public function show(string $id): JsonResponse {
        $relations    = $this->organizationService->parseRelationships(request('with'));
        $organization = $this->organizationService->findById($id, ['*'], $relations);

        return ApiResponseMiddleware::showResponse(
            new OrganizationResource($organization),
            'organization',
            $organization->toArray(),
        );
    }

    /**
     * Add a new Organization
     */
    public function store(OrganizationRequest $request): JsonResponse {
        $organization = $this->organizationService->createOrganization($request->validated());

        return ApiResponseMiddleware::createResponse(
            new OrganizationResource($organization),
            'organization',
            $organization->toArray(),
        );
    }

    /**
     * Update a specific Organization
     */
    public function update(OrganizationRequest $request, string $id): JsonResponse {
        $updatedOrganization = $this->organizationService->updateOrganization($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new OrganizationResource($updatedOrganization),
            'organization',
            $updatedOrganization->toArray(),
        );
    }
}
