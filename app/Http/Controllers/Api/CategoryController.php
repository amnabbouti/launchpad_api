<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    // All
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->all();
        return $this->successResponse(CategoryResource::collection($categories));
    }

    // Create
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());
        return $this->successResponse(new CategoryResource($category), 'Category created successfully', 201);
    }

    // Show
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        if (! $category) {
            return $this->errorResponse('Category not found', 404);
        }
        return $this->successResponse(new CategoryResource($category));
    }

    // Update
    public function update(CategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        if (! $category) {
            return $this->errorResponse('Category not found', 404);
        }
        $updatedCategory = $this->categoryService->update($id, $request->validated());
        return $this->successResponse(new CategoryResource($updatedCategory), 'Category updated successfully');
    }

    // Delete
    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        if (! $category) {
            return $this->errorResponse('Category not found', 404);
        }
        $this->categoryService->delete($id);
        return $this->successResponse(null, 'Category deleted successfully');
    }

    // With items
    public function getWithItems(): JsonResponse
    {
        $categories = $this->categoryService->getWithItems();
        return $this->successResponse(CategoryResource::collection($categories));
    }

    // By name
    public function getByName(string $name): JsonResponse
    {
        $categories = $this->categoryService->getByName($name);
        return $this->successResponse(CategoryResource::collection($categories));
    }

    // Active
    public function getActive(): JsonResponse
    {
        $categories = $this->categoryService->getActive();
        return $this->successResponse(CategoryResource::collection($categories));
    }
}
