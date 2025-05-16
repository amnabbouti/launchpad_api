<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Collection;

class ItemController extends BaseController
{
    protected ItemService $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    // All
    public function index(): JsonResponse
    {
        $items = $this->itemService->all(['*'], ['maintenances']);
        return $this->successResponse(
            ItemResource::collection($items)
        );
    }

    // Create
    public function store(ItemRequest $request): JsonResponse
    {
        $item = $this->itemService->create($request->validated());
        return $this->successResponse(
            new ItemResource($item),
            'Item created successfully',
            self::HTTP_CREATED
        );
    }

    // Show
    public function show(int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);
        
        if (! $item) {
            return $this->errorResponse('Item not found', self::HTTP_NOT_FOUND);
        }
        
        return $this->successResponse(new ItemResource($item));
    }

    // Update
    public function update(ItemRequest $request, int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);
        
        if (! $item) {
            return $this->errorResponse('Item not found', self::HTTP_NOT_FOUND);
        }
        
        $updatedItem = $this->itemService->update($id, $request->validated());
        
        return $this->successResponse(
            new ItemResource($updatedItem),
            'Item updated successfully'
        );
    }

    // Delete
    public function destroy(int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);
        
        if (! $item) {
            return $this->errorResponse('Item not found', self::HTTP_NOT_FOUND);
        }
        
        $this->itemService->delete($id);
        
        return $this->successResponse(
            null,
            'Item deleted successfully'
        );
    }

    // By category
    public function getByCategory(int $categoryId): JsonResponse
    {
        $items = $this->itemService->getByCategory($categoryId);
        
        return $this->successResponse(
            ItemResource::collection($items)
        );
    }

    // By stock
    public function getByStock(int $stockId): JsonResponse
    {
        $items = $this->itemService->getByStock($stockId);
        
        return $this->successResponse(
            ItemResource::collection($items)
        );
    }

    // Active
    public function getActive(): JsonResponse
    {
        $items = $this->itemService->getActive();
        
        return $this->successResponse(
            ItemResource::collection($items)
        );
    }

    // Locations
    public function itemLocations(int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);
        
        if (! $item) {
            return $this->errorResponse('Item not found', self::HTTP_NOT_FOUND);
        }
        
        $locations = $this->itemService->getItemLocations($id);
        
        return $this->successResponse($locations);
    }

    // Get by code
    public function getByCode(string $code): JsonResponse
    {
        $items = $this->itemService->getByCode($code);
        return $this->successResponse(ItemResource::collection($items));
    }
}
