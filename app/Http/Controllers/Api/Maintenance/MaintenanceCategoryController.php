<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\MaintenanceCategoryRequest;
use App\Http\Resources\MaintenanceCategoryResource;
use App\Services\MaintenanceCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceCategoryController extends BaseController
{
    public function __construct(
        private MaintenanceCategoryService $maintenanceCategoryService,
    ) {}

    /**
     * Get maintenance categories with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->maintenanceCategoryService->processRequestParams($request->query());
        $categories = $this->maintenanceCategoryService->getFiltered($filters);

        return $this->successResponse(MaintenanceCategoryResource::collection($categories));
    }

    /**
     * Create a new maintenance category.
     */
    public function store(MaintenanceCategoryRequest $request): JsonResponse
    {
        $category = $this->maintenanceCategoryService->createMaintenanceCategory($request->validated());

        return $this->successResponse(
            new MaintenanceCategoryResource($category),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific maintenance category.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));

        $category = $this->maintenanceCategoryService->findById($id, $with);

        return $this->successResponse(new MaintenanceCategoryResource($category));
    }

    /**
     * Update a maintenance category.
     */
    public function update(MaintenanceCategoryRequest $request, int $id): JsonResponse
    {
        $updatedCategory = $this->maintenanceCategoryService->updateMaintenanceCategory($id, $request->validated());

        return $this->successResponse(
            new MaintenanceCategoryResource($updatedCategory),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a maintenance category.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->maintenanceCategoryService->delete($id);

        return $this->successResponse(null, SuccessMessages::RESOURCE_DELETED);
    }
}
