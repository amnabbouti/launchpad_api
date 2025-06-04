<?php

namespace App\Http\Controllers\Api\Location;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends BaseController
{
    public function __construct(
        private LocationService $locationService,
    ) {}

    /**
     * All locations.
     */
    public function index(Request $request): JsonResponse
    {        // service handles relationship processing
        $filters = $this->locationService->processRequestParams($request->query());
        $locations = $this->locationService->getFiltered($filters);

        return $this->successResponse(LocationResource::collection($locations));
    }

    /**
     * Create a new location.
     */
    public function store(LocationRequest $request): JsonResponse
    {
        $location = $this->locationService->createLocation($request->validated());

        return $this->resourceResponse(
            new LocationResource($location),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific location by ID with optional relationships.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // Let service handle relationship processing
        $processed = $this->locationService->processRequestParams($request->query());

        $location = $this->locationService->findById($id, ['*'], $processed['with'] ?? []);

        return
        $this->resourceResponse(
            new LocationResource($location),
            SuccessMessages::RESOURCE_RETRIEVED,
        );
    }

    /**
     * Update a specific location.
     */
    public function update(LocationRequest $request, string $id): JsonResponse
    {
        $updatedLocation = $this->locationService->update($id, $request->validated());

        return $this->resourceResponse(
            new LocationResource($updatedLocation),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Remove a specific location by ID.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->locationService->delete($id);

        return $this->successResponse(null, SuccessMessages::RESOURCE_DELETED);
    }
}
