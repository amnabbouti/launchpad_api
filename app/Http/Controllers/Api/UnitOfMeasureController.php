<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UnitOfMeasureRequest;
use App\Http\Resources\UnitOfMeasureResource;
use App\Services\UnitOfMeasureService;
use Illuminate\Http\JsonResponse;

class UnitOfMeasureController extends BaseController
{
    protected $unitOfMeasureService;

    // create
    public function __construct(UnitOfMeasureService $unitOfMeasureService)
    {
        $this->unitOfMeasureService = $unitOfMeasureService;
    }

    // All
    public function index(): JsonResponse
    {
        $units = $this->unitOfMeasureService->all();
        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }

    // Create new
    public function store(UnitOfMeasureRequest $request): JsonResponse
    {
        $unit = $this->unitOfMeasureService->create($request->validated());
        return $this->successResponse(new UnitOfMeasureResource($unit), 'Unit of measure created successfully', 201);
    }

    // Get by ID
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);
        if (! $unit) {
            return $this->errorResponse('Unit of measure not found', 404);
        }
        return $this->successResponse(new UnitOfMeasureResource($unit));
    }

    // Update existing
    public function update(UnitOfMeasureRequest $request, int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);
        if (! $unit) {
            return $this->errorResponse('Unit of measure not found', 404);
        }
        $updatedUnit = $this->unitOfMeasureService->update($id, $request->validated());
        return $this->successResponse(new UnitOfMeasureResource($updatedUnit), 'Unit of measure updated successfully');
    }

    // Delete existing
    public function destroy(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);
        if (! $unit) {
            return $this->errorResponse('Unit of measure not found', 404);
        }
        $this->unitOfMeasureService->delete($id);
        return $this->successResponse(null, 'Unit of measure deleted successfully');
    }

    // Get by name
    public function getByName(string $name): JsonResponse
    {
        $units = $this->unitOfMeasureService->getByName($name);
        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }

    // Get active
    public function getActive(): JsonResponse
    {
        $units = $this->unitOfMeasureService->getActive();
        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }
}
