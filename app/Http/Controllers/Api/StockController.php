<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StockRequest;
use App\Http\Resources\StockResource;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends BaseController
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    // display all stocks
    public function index(): JsonResponse
    {
        $stocks = $this->stockService->all();
        return $this->successResponse(StockResource::collection($stocks));
    }

    // store a new stock
    public function store(StockRequest $request): JsonResponse
    {
        $stock = $this->stockService->create($request->validated());
        return $this->successResponse(new StockResource($stock), 'Stock created successfully', 201);
    }

    // display specified stock
    public function show(int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);

        if (!$stock) {
            return $this->errorResponse('Stock not found', 404);
        }

        return $this->successResponse(new StockResource($stock));
    }

    // update a stock
    public function update(StockRequest $request, int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);

        if (!$stock) {
            return $this->errorResponse('Stock not found', 404);
        }

        $updatedStock = $this->stockService->update($id, $request->validated());
        return $this->successResponse(new StockResource($updatedStock), 'Stock updated successfully');
    }

    // remove a stock
    public function destroy(int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);

        if (!$stock) {
            return $this->errorResponse('Stock not found', 404);
        }

        $this->stockService->delete($id);
        return $this->successResponse(null, 'Stock deleted successfully');
    }

    // get stock that contain items
    public function getWithItems(): JsonResponse
    {
        $stocks = $this->stockService->getWithItems();
        return $this->successResponse(StockResource::collection($stocks));
    }

    // get an active stock
    public function getActive(): JsonResponse
    {
        $stocks = $this->stockService->getActive();
        return $this->successResponse(StockResource::collection($stocks));
    }
}
