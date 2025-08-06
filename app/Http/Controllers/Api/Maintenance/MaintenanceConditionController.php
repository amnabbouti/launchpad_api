<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\MaintenanceConditionRequest;
use App\Http\Resources\MaintenanceConditionResource;
use App\Services\MaintenanceConditionService;
use Illuminate\Http\JsonResponse;

class MaintenanceConditionController extends BaseController
{
    public function __construct(
        private readonly MaintenanceConditionService $maintenanceConditionService,
    ) {}

    /**
     * Get maintenance conditions with optional filters.
     */
    public function index(): JsonResponse
    {
        $request = request();
        $processedParams = $this->maintenanceConditionService->processRequestParams($request->query());
        $maintenanceConditionsQuery = $this->maintenanceConditionService->getFiltered($processedParams);
        $totalCount = $maintenanceConditionsQuery->count();

        $maintenanceConditions = $this->paginated($maintenanceConditionsQuery, $request);

        return ApiResponseMiddleware::listResponse(
            MaintenanceConditionResource::collection($maintenanceConditions),
            'maintenance_condition',
            $totalCount
        );
    }

    /**
     * Create a new maintenance condition.
     */
    public function store(MaintenanceConditionRequest $request): JsonResponse
    {
        $maintenanceCondition = $this->maintenanceConditionService->createMaintenanceCondition($request->validated());

        return ApiResponseMiddleware::createResponse(
            new MaintenanceConditionResource($maintenanceCondition),
            'maintenance_condition',
            $maintenanceCondition->toArray()
        );
    }

    /**
     * Get a specific maintenance condition.
     */
    public function show(int $id): JsonResponse
    {
        $request = request();
        $processedParams = $this->maintenanceConditionService->processRequestParams($request->query());
        $with = $processedParams['with'] ?? [];

        $maintenanceCondition = $this->maintenanceConditionService->find($id, $with);

        return ApiResponseMiddleware::showResponse(
            new MaintenanceConditionResource($maintenanceCondition),
            'maintenance_condition',
            $maintenanceCondition->toArray()
        );
    }

    /**
     * Update a maintenance condition.
     */
    public function update(MaintenanceConditionRequest $request, int $id): JsonResponse
    {
        $updatedMaintenanceCondition = $this->maintenanceConditionService->updateMaintenanceCondition($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new MaintenanceConditionResource($updatedMaintenanceCondition),
            'maintenance_condition',
            $updatedMaintenanceCondition->toArray()
        );
    }

    /**
     * Delete a maintenance condition.
     */
    public function destroy(int $id): JsonResponse
    {
        $maintenanceCondition = $this->maintenanceConditionService->find($id);
        $this->maintenanceConditionService->delete($id);

        return ApiResponseMiddleware::deleteResponse(
            'maintenance_condition',
            $maintenanceCondition->toArray()
        );
    }
}
