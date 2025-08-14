<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\UnitOfMeasureRequest;
use App\Http\Resources\UnitOfMeasureResource;
use App\Services\UnitOfMeasureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitOfMeasureController extends BaseController {
    /**
     * Create a new controller instance with the unit of measure service.
     */
    public function __construct(
        private readonly UnitOfMeasureService $unitOfMeasureService,
    ) {}

    /**
     * Delete a unit of measure.
     */
    public function destroy(string $id): JsonResponse {
        $this->unitOfMeasureService->delete($id);

        return ApiResponseMiddleware::deleteResponse('unitofmeasure');
    }

    /**
     * Get all units of measure.
     */
    public function index(Request $request): JsonResponse {
        $filters    = $this->unitOfMeasureService->processRequestParams($request->all());
        $unitsQuery = $this->unitOfMeasureService->getFiltered($filters);
        $totalCount = $unitsQuery->count();

        $units = $this->paginated($unitsQuery, $request);

        return ApiResponseMiddleware::listResponse(
            UnitOfMeasureResource::collection($units),
            'unitofmeasure',
            $totalCount,
        );
    }

    /**
     * Get a specific unit of measure.
     */
    public function show(string $id): JsonResponse {
        $unit = $this->unitOfMeasureService->findById($id);

        return ApiResponseMiddleware::showResponse(
            new UnitOfMeasureResource($unit),
            'unitofmeasure',
            $unit->toArray(),
        );
    }

    /**
     * Create a new unit of measure.
     */
    public function store(UnitOfMeasureRequest $request): JsonResponse {
        $unit = $this->unitOfMeasureService->createUnitOfMeasure($request->validated());

        return ApiResponseMiddleware::createResponse(
            new UnitOfMeasureResource($unit),
            'unitofmeasure',
            $unit->toArray(),
        );
    }

    /**
     * Update a unit of measure.
     */
    public function update(UnitOfMeasureRequest $request, string $id): JsonResponse {
        $updatedUnit = $this->unitOfMeasureService->updateUnitOfMeasure($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new UnitOfMeasureResource($updatedUnit),
            'unitofmeasure',
            $updatedUnit->toArray(),
        );
    }
}
