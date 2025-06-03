<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\MaintenanceRequest;
use App\Http\Resources\MaintenanceResource;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends BaseController
{
    public function __construct(
        private MaintenanceService $maintenanceService,
    ) {}

    // All
    public function index(MaintenanceRequest $request): JsonResponse
    {
        $filters = $this->maintenanceService->processRequestParams($request->query());
        $maintenances = $this->maintenanceService->getFiltered($filters);
        $resourceType = 'maintenances';

        // Check if results are empty
        if ($maintenances->isEmpty()) {
            $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));

            if ($hasFilters) {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            $message = str_replace('Resources', ucfirst($resourceType), SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(MaintenanceResource::collection($maintenances), $message);
    }

    // Show
    public function show($id): JsonResponse
    {
        $maintenance = $this->maintenanceService->findById($id);

        return $this->successResponse(new MaintenanceResource($maintenance));
    }

    // Create
    public function store(MaintenanceRequest $request): JsonResponse
    {
        $maintenance = $this->maintenanceService->create($request->validated());

        return $this->successResponse(
            new MaintenanceResource($maintenance),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    // Update
    public function update(MaintenanceRequest $request, $id): JsonResponse
    {
        $updatedMaintenance = $this->maintenanceService->update($id, $request->validated());

        return $this->successResponse(
            new MaintenanceResource($updatedMaintenance),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    // Delete
    public function destroy($id): JsonResponse
    {
        $this->maintenanceService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
