<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends BaseController
{
    protected $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    public function index(): JsonResponse
    {
        $items = $this->itemService->all();
        return $this->successResponse(ItemResource::collection($items));
    }

    public function store(ItemRequest $request): JsonResponse
    {
        $item = $this->itemService->create($request->validated());
        return $this->successResponse(new ItemResource($item), 'Item created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);

        if (!$item) {
            return $this->errorResponse('Item not found', 404);
        }

        return $this->successResponse(new ItemResource($item));
    }

    public function update(ItemRequest $request, int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);

        if (!$item) {
            return $this->errorResponse('Item not found', 404);
        }

        $updatedItem = $this->itemService->update($id, $request->validated());
        return $this->successResponse(new ItemResource($updatedItem), 'Item updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);

        if (!$item) {
            return $this->errorResponse('Item not found', 404);
        }

        $this->itemService->delete($id);
        return $this->successResponse(null, 'Item deleted successfully');
    }

    // get item by category
    public function getByCategory(int $categoryId): JsonResponse
    {
        $items = $this->itemService->getByCategory($categoryId);
        return $this->successResponse(ItemResource::collection($items));
    }

    // get item by stock
    public function getByStock(int $stockId): JsonResponse
    {
        $items = $this->itemService->getByStock($stockId);
        return $this->successResponse(ItemResource::collection($items));
    }

    // get active item
    public function getActive(): JsonResponse
    {
        $items = $this->itemService->getActive();
        return $this->successResponse(ItemResource::collection($items));
    }

    // get all locations of an item
    public function itemLocations(int $id): JsonResponse
    {
        $item = $this->itemService->findById($id);

        if (!$item) {
            return $this->errorResponse('Item not found', 404);
        }

        $locations = $this->itemService->getItemLocations($id);
        return $this->successResponse($locations);
    }
}
