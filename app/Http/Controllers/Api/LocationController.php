<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;

class LocationController extends BaseController
{
    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    // All
    public function index(): JsonResponse
    {
        $locations = \App\Models\Location::with('childrens')->whereNull('parent_id')->get();
        return $this->successResponse(LocationResource::collection($locations));
    }

    // Create
    public function store(LocationRequest $request): JsonResponse
    {
        $location = $this->locationService->create($request->validated());
        return $this->successResponse(new LocationResource($location), 'Location created successfully', 201);
    }

    // Show
    public function show(int $id): JsonResponse
    {
        $location = $this->locationService->findById($id, ['*'], ['childrens']);
        if (! $location) {
            return $this->errorResponse('Location not found', 404);
        }
        return $this->successResponse(new LocationResource($location));
    }

    // Update
    public function update(LocationRequest $request, int $id): JsonResponse
    {
        $location = $this->locationService->findById($id);
        if (! $location) {
            return $this->errorResponse('Location not found', 404);
        }
        $updatedLocation = $this->locationService->update($id, $request->validated());
        return $this->successResponse(new LocationResource($updatedLocation), 'Location updated successfully');
    }

    // Delete
    public function destroy(int $id): JsonResponse
    {
        $location = $this->locationService->findById($id);
        if (! $location) {
            return $this->errorResponse('Location not found', 404);
        }
        $this->locationService->delete($id);
        return $this->successResponse(null, 'Location deleted successfully');
    }

    // With items
    public function getWithItems(): JsonResponse
    {
        $locations = $this->locationService->getWithItems();
        return $this->successResponse(LocationResource::collection($locations));
    }

    // Active
    public function getActive(): JsonResponse
    {
        $locations = $this->locationService->getActive();
        return $this->successResponse(LocationResource::collection($locations));
    }
}
