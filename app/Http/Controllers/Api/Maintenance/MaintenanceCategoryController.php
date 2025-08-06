<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\MaintenanceCategoryRequest;
use App\Http\Resources\MaintenanceCategoryResource;
use App\Services\MaintenanceCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceCategoryController extends BaseController
{
    public function __construct(
        private readonly MaintenanceCategoryService $maintenanceCategoryService,
    ) {}

    /**
     * Get maintenance categories with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->maintenanceCategoryService->processRequestParams($request->query());
        $categoriesQuery = $this->maintenanceCategoryService->getFiltered($filters);
        $totalCount = $categoriesQuery->count();

        $categories = $this->paginated($categoriesQuery, $request);

        return ApiResponseMiddleware::listResponse(
            MaintenanceCategoryResource::collection($categories),
            'maintenance_category',
            $totalCount
        );
    }

    /**
     * Create a new maintenance category.
     */
    public function store(MaintenanceCategoryRequest $request): JsonResponse
    {
        $category = $this->maintenanceCategoryService->createMaintenanceCategory($request->validated());

        return ApiResponseMiddleware::createResponse(
            new MaintenanceCategoryResource($category),
            'maintenance_category',
            $category->toArray()
        );
    }

    /**
     * Get a specific maintenance category.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));

        $category = $this->maintenanceCategoryService->findById($id, $with);

        return ApiResponseMiddleware::showResponse(
            new MaintenanceCategoryResource($category),
            'maintenance_category',
            $category->toArray()
        );
    }

    /**
     * Update a maintenance category.
     */
    public function update(MaintenanceCategoryRequest $request, int $id): JsonResponse
    {
        $updatedCategory = $this->maintenanceCategoryService->updateMaintenanceCategory($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new MaintenanceCategoryResource($updatedCategory),
            'maintenance_category',
            $updatedCategory->toArray()
        );
    }

    /**
     * Delete a maintenance category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = $this->maintenanceCategoryService->findById($id);
        $this->maintenanceCategoryService->delete($id);

        return ApiResponseMiddleware::deleteResponse(
            'maintenance_category',
            $category->toArray()
        );
    }
}
