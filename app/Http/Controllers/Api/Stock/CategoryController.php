<?php

namespace App\Http\Controllers\Api\Stock;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    /**
     * Create a new controller instance with the category service.
     */
    public function __construct(
        private CategoryService $categoryService,
    ) {}

    /**
     * Get all categories.
     */
    public function index(CategoryRequest $request): JsonResponse
    {
        $filters = $this->categoryService->processRequestParams($request->query());
        $categories = $this->categoryService->getFiltered($filters);
        $resourceType = 'categories';

        // Check if results are empty
        if ($categories->isEmpty()) {
            $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));

            if ($hasFilters) {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            $message = str_replace('Resources', ucfirst($resourceType), SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(CategoryResource::collection($categories), $message);
    }

    /**
     * Create a new category.
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return $this->successResponse(
            new CategoryResource($category),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific category.
     */
    public function show($id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * Update a category.
     */
    public function update(CategoryRequest $request, $id): JsonResponse
    {
        $updatedCategory = $this->categoryService->update($id, $request->validated());

        return $this->successResponse(
            new CategoryResource($updatedCategory),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a category.
     */
    public function destroy($id): JsonResponse
    {
        $this->categoryService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
