<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\OrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;

class OrganizationController extends BaseController
{
    protected $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * Display Organizations
     */
    public function index(): JsonResponse
    {
        $relations = $this->organizationService->parseRelationships(request('with'));
        $organizations = $this->organizationService->all(['*'], $relations);

        return $this->successResponse(OrganizationResource::collection($organizations));
    }

    /**
     * Add a new Organization
     */
    public function store(OrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->create($request->validated());

        return $this->successResponse(
            new OrganizationResource($organization),
            'Organization created successfully',
            self::HTTP_CREATED,
        );
    }

    /**
     * Display a specific Organization
     */
    public function show(int $id): JsonResponse
    {
        $relations = $this->organizationService->parseRelationships(request('with'));
        $organization = $this->organizationService->findById($id, ['*'], $relations);

        return $this->successResponse(new OrganizationResource($organization));
    }

    /**
     * Update a specific Organization
     */
    public function update(OrganizationRequest $request, int $id): JsonResponse
    {
        $updatedOrganization = $this->organizationService->update($id, $request->validated());

        return $this->successResponse(
            new OrganizationResource($updatedOrganization),
            'Organization updated successfully',
        );
    }

    /**
     * Delete a specific Organization
     */
    public function destroy(int $id): JsonResponse
    {
        $this->organizationService->delete($id);

        return $this->successResponse(null, 'Organization deleted successfully');
    }

    /**
     * Get active organizations
     */
    public function getActive(): JsonResponse
    {
        $organizations = $this->organizationService->getActive();

        return $this->successResponse(OrganizationResource::collection($organizations));
    }
}
