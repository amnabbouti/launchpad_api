<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
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
        private readonly UnitOfMeasureService $unitOfMeasureService,
    ) {}

    /**
     * Get all units of measure.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->unitOfMeasureService->processRequestParams($request->all());
        $unitsQuery = $this->unitOfMeasureService->getFiltered($filters);
        $totalCount = $unitsQuery->count();

        $units = $this->paginated($unitsQuery, $request);

        return ApiResponseMiddleware::listResponse(
            UnitOfMeasureResource::collection($units),
            'unitofmeasure',
            $totalCount
        );
    }

    /**
     * Create a new unit of measure.
     */
    public function store(UnitOfMeasureRequest $request): JsonResponse
    {
        $unit = $this->unitOfMeasureService->createUnitOfMeasure($request->validated());

        return ApiResponseMiddleware::createResponse(
            new UnitOfMeasureResource($unit),
            'unitofmeasure',
            $unit->toArray()
        );
    }

    /**
     * Get a specific unit of measure.
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitOfMeasureService->findById($id);

        return ApiResponseMiddleware::showResponse(
            new UnitOfMeasureResource($unit),
            'unitofmeasure',
            $unit->toArray()
        );
    }

    /**
     * Update a unit of measure.
     */
    public function update(UnitOfMeasureRequest $request, int $id): JsonResponse
    {
        $updatedUnit = $this->unitOfMeasureService->updateUnitOfMeasure($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new UnitOfMeasureResource($updatedUnit),
            'unitofmeasure',
            $updatedUnit->toArray()
        );
    }

    /**
     * Delete a unit of measure.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->unitOfMeasureService->delete($id);

        return ApiResponseMiddleware::deleteResponse('unitofmeasure');
    }
}
