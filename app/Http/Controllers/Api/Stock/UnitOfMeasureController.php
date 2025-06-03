<?php

namespace App\Http\Controllers\Api\Stock;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UnitOfMeasureRequest;
use App\Http\Resources\UnitOfMeasureResource;
use App\Services\UnitOfMeasureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitOfMeasureController extends BaseController
{
    /**
     * Create a new controller instance with the unit of measure service.
     */
    public function __construct(
        private UnitOfMeasureService $unitOfMeasureService,
    ) {}

    /**
     * Get all units of measure.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->unitOfMeasureService->processRequestParams($request->all());
        $units = $this->unitOfMeasureService->getFiltered($filters);

        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }

    /**
     * Create a new unit of measure.
     */
    public function store(UnitOfMeasureRequest $request): JsonResponse
    {
        $unit = $this->unitOfMeasureService->create($request->validated());

        return $this->successResponse(
            new UnitOfMeasureResource($unit),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific unit of measure.
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        if (! $unit) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        return $this->successResponse(new UnitOfMeasureResource($unit));
    }

    /**
     * Update a unit of measure.
     */
    public function update(UnitOfMeasureRequest $request, int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        if (! $unit) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        $updatedUnit = $this->unitOfMeasureService->update($id, $request->validated());

        return $this->successResponse(
            new UnitOfMeasureResource($updatedUnit),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a unit of measure.
     */
    public function destroy(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        if (! $unit) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        $this->unitOfMeasureService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }

    /**
     * Get units of measure by name.
     */
    public function getByName(string $name): JsonResponse
    {
        $units = $this->unitOfMeasureService->getByName($name);

        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }

    /**
     * Get active units of measure.
     */
    public function getActive(): JsonResponse
    {
        $units = $this->unitOfMeasureService->getActive();

        return $this->successResponse(UnitOfMeasureResource::collection($units));
    }
}
