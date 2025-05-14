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

    // display all
    public function index(): JsonResponse
    {
        $units = $this->unitOfMeasureService->all();

        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }

    // store
    public function store(UnitOfMeasureRequest $request): JsonResponse
    {
        $unit = $this->unitOfMeasureService->create($request->validated());

        return $this->successResponse(new UnitOfMeasureResource($unit), 'Unit of measure created successfully', 201);
    }

    // display a specific unit
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        if (! $unit) {
            return $this->errorResponse('Unit of measure not found', 404);
        }

        return $this->successResponse(new UnitOfMeasureResource($unit));
    }

    // update a unit
    public function update(UnitOfMeasureRequest $request, int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        if (! $unit) {
            return $this->errorResponse('Unit of measure not found', 404);
        }

        $updatedUnit = $this->unitOfMeasureService->update($id, $request->validated());

        return $this->successResponse(new UnitOfMeasureResource($updatedUnit), 'Unit of measure updated successfully');
    }

    // remove a unit
    public function destroy(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        if (! $unit) {
            return $this->errorResponse('Unit of measure not found', 404);
        }

        $this->unitOfMeasureService->delete($id);

        return $this->successResponse(null, 'Unit of measure deleted successfully');
    }

    // get units by name
    public function getByName(string $name): JsonResponse
    {
        $units = $this->unitOfMeasureService->getByName($name);

        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }

    // get active units
    public function getActive(): JsonResponse
    {
        $units = $this->unitOfMeasureService->getActive();

        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }
}
