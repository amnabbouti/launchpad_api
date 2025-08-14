<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends BaseController {
    public function __construct(
        private readonly LocationService $locationService,
    ) {}

    /**
     * Remove a specific location by ID.
     */
    public function destroy(string $id): JsonResponse {
        $location = $this->locationService->findById($id);
        $this->locationService->delete($id);

        return ApiResponseMiddleware::deleteResponse('location', $location->toArray());
    }

    /**
     * All locations.
     */
    public function index(Request $request): JsonResponse {
        $filters = $this->locationService->processRequestParams($request->query());
        $query   = $this->locationService->getFiltered($filters);

        $wantsHierarchy = $filters['hierarchy'] ?? true;

        $hasFilters = ! empty($filters['name']) || ! empty($filters['code'])
            || ! empty($filters['description']) || isset($filters['is_active'])
            || isset($filters['parent_id']);

        if ($hasFilters) {
            $wantsHierarchy = false;
        }

        if ($wantsHierarchy) {
            $query->whereNull('parent_id');
        }

        $locations = $this->paginated($query, $request);

        $totalCount = $wantsHierarchy
            ? $this->locationService->getFiltered(array_merge($filters, ['hierarchy' => false]))->count()
            : null;

        return ApiResponseMiddleware::listResponse(
            LocationResource::collection($locations),
            'location',
            $totalCount,
        );
    }

    /**
     * Get a specific location by ID with optional relationships.
     */
    public function show(Request $request, string $id): JsonResponse {
        $location = $this->locationService->findWithHierarchy($id);

        return ApiResponseMiddleware::showResponse(
            new LocationResource($location),
            'location',
            $location->toArray(),
        );
    }

    /**
     * Create a new location.
     */
    public function store(LocationRequest $request): JsonResponse {
        $location = $this->locationService->createLocation($request->validated());

        return ApiResponseMiddleware::createResponse(
            new LocationResource($location),
            'location',
            $location->toArray(),
        );
    }

    /**
     * Update a specific location.
     */
    public function update(LocationRequest $request, string $id): JsonResponse {
        $updatedLocation = $this->locationService->update($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new LocationResource($updatedLocation),
            'location',
            $updatedLocation->toArray(),
        );
    }
}
