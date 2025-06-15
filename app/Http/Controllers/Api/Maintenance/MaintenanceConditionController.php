<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\MaintenanceConditionRequest;
use App\Http\Resources\MaintenanceConditionResource;
use App\Services\MaintenanceConditionService;
use Illuminate\Http\JsonResponse;

class MaintenanceConditionController extends BaseController
{
    public function __construct(
        private MaintenanceConditionService $maintenanceConditionService,
    ) {}

    /**
     * Get maintenance conditions with optional filters.
     */
    public function index(): JsonResponse
    {
        $request = request();
        $processedParams = $this->maintenanceConditionService->processRequestParams($request->query());
        $maintenanceConditions = $this->maintenanceConditionService->getFiltered($processedParams);

        return $this->successResponse(MaintenanceConditionResource::collection($maintenanceConditions));
    }

    /**
     * Create a new maintenance condition.
     */
    public function store(MaintenanceConditionRequest $request): JsonResponse
    {
        $maintenanceCondition = $this->maintenanceConditionService->createMaintenanceCondition($request->validated());

        return $this->successResponse(
            new MaintenanceConditionResource($maintenanceCondition),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
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

        return $this->successResponse(new MaintenanceConditionResource($maintenanceCondition));
    }

    /**
     * Update a maintenance condition.
     */
    public function update(MaintenanceConditionRequest $request, int $id): JsonResponse
    {
        $updatedMaintenanceCondition = $this->maintenanceConditionService->updateMaintenanceCondition($id, $request->validated());

        return $this->successResponse(
            new MaintenanceConditionResource($updatedMaintenanceCondition),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a maintenance condition.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->maintenanceConditionService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
