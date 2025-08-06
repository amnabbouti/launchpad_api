<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\MaintenanceRequest;
use App\Http\Resources\MaintenanceResource;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends BaseController
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService,
    ) {}

    public function index(MaintenanceRequest $request): JsonResponse
    {
        $filters = $this->maintenanceService->processRequestParams($request->query());

        if (! isset($filters['with'])) {
            $filters['with'] = ['maintainable', 'user', 'supplier'];
        }

        $query = $this->maintenanceService->getFiltered($filters);
        $maintenances = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            MaintenanceResource::collection($maintenances),
            'maintenance',
            $maintenances->total()
        );
    }

    /**
     * Get a specific maintenance.
     */
    public function show($id): JsonResponse
    {
        $maintenance = $this->maintenanceService->findByIdWithRelations($id);

        return ApiResponseMiddleware::showResponse(
            new MaintenanceResource($maintenance),
            'maintenance',
            $maintenance->toArray()
        );
    }

    /**
     * Update a maintenance.
     */
    public function update(MaintenanceRequest $request, $id): JsonResponse
    {
        $updatedMaintenance = $this->maintenanceService->update($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new MaintenanceResource($updatedMaintenance),
            'maintenance',
            $updatedMaintenance->toArray()
        );
    }

    /**
     * Delete a maintenance.
     */
    public function destroy($id): JsonResponse
    {
        $maintenance = $this->maintenanceService->findById($id);
        $this->maintenanceService->delete($id);

        return ApiResponseMiddleware::deleteResponse(
            'maintenance',
            $maintenance->toArray()
        );
    }

    /**
     * Create a maintenance record for an item.
     */
    public function createItemMaintenance(MaintenanceRequest $request): JsonResponse
    {
        $maintenance = $this->maintenanceService->createItemMaintenance($request->validated());

        return ApiResponseMiddleware::createResponse(
            new MaintenanceResource($maintenance),
            'maintenance',
            $maintenance->toArray()
        );
    }

    /**
     * Complete an active maintenance record.
     */
    public function completeMaintenance(MaintenanceRequest $request, $id): JsonResponse
    {
        $maintenance = $this->maintenanceService->completeMaintenance($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new MaintenanceResource($maintenance),
            'maintenance',
            $maintenance->toArray()
        );
    }

    /**
     * Create maintenance from a condition trigger.
     */
    public function createFromCondition(MaintenanceRequest $request): JsonResponse
    {
        $maintenance = $this->maintenanceService->createFromCondition($request->validated());

        return ApiResponseMiddleware::createResponse(
            new MaintenanceResource($maintenance),
            'maintenance',
            $maintenance->toArray()
        );
    }
}
