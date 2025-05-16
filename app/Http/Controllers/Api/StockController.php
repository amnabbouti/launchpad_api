<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StockRequest;
use App\Http\Resources\StockResource;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends BaseController
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    // All
    public function index(): JsonResponse
    {
        $stocks = $this->stockService->all();
        return $this->successResponse(StockResource::collection($stocks));
    }

    // Create
    public function store(StockRequest $request): JsonResponse
    {
        $stock = $this->stockService->create($request->validated());
        return $this->successResponse(new StockResource($stock), 'Stock created successfully', 201);
    }

    // Show
    public function show(int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);
        if (! $stock) {
            return $this->errorResponse('Stock not found', 404);
        }
        return $this->successResponse(new StockResource($stock));
    }

    // Update
    public function update(StockRequest $request, int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);
        if (! $stock) {
            return $this->errorResponse('Stock not found', 404);
        }
        $updatedStock = $this->stockService->update($id, $request->validated());
        return $this->successResponse(new StockResource($updatedStock), 'Stock updated successfully');
    }

    // Delete
    public function destroy(int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);
        if (! $stock) {
            return $this->errorResponse('Stock not found', 404);
        }
        $this->stockService->delete($id);
        return $this->successResponse(null, 'Stock deleted successfully');
    }

    // With items
    public function getWithItems(): JsonResponse
    {
        $stocks = $this->stockService->getWithItems();
        return $this->successResponse(StockResource::collection($stocks));
    }

    // Active
    public function getActive(): JsonResponse
    {
        $stocks = $this->stockService->getActive();
        return $this->successResponse(StockResource::collection($stocks));
    }
}
