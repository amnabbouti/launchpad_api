<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
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
        $query = $this->categoryService->getFiltered($filters);

        $wantsHierarchy = $request->query('hierarchy', true);
        if ($wantsHierarchy && $wantsHierarchy !== 'false') {
            $query->whereNull('parent_id')
                ->with('childrenRecursive');
        }

        $categories = $this->paginated($query, $request);

        $totalCount = $wantsHierarchy
            ? $this->categoryService->getFiltered(array_merge($filters, ['hierarchy' => false]))->count()
            : null;

        return ApiResponseMiddleware::listResponse(
            CategoryResource::collection($categories),
            'category',
            $totalCount
        );
    }

    /**
     * Create a new category.
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return ApiResponseMiddleware::createResponse(
            new CategoryResource($category),
            'category',
            $category->toArray()
        );
    }

    /**
     * Get a specific category.
     */
    public function show($id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        return ApiResponseMiddleware::showResponse(
            new CategoryResource($category),
            'category',
            $category->toArray()
        );
    }

    /**
     * Update a category.
     */
    public function update(CategoryRequest $request, $id): JsonResponse
    {
        $updatedCategory = $this->categoryService->update($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new CategoryResource($updatedCategory),
            'category',
            $updatedCategory->toArray()
        );
    }

    /**
     * Delete a category.
     */
    public function destroy($id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        $this->categoryService->delete($id);

        return ApiResponseMiddleware::deleteResponse('category', $category->toArray());
    }
}
