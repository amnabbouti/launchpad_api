<?php

namespace App\Http\Controllers\Api\Stock;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StockItemRequest;
use App\Http\Resources\StockItemResource;
use App\Services\StockItemService;
use Illuminate\Http\JsonResponse;

class StockItemController extends BaseController
{
    public function __construct(
        private StockItemService $stockItemService,
    ) {}

    /**
     * Get stock items with optional filters.
     */
    public function index(): JsonResponse
    {
        $request = request();
        $filters = [
            'stock_id' => $request->query('stock_id', null, 'intval'),
            'item_id' => $request->query('item_id', null, 'intval'),
            'status_id' => $request->query('status_id', null, 'intval'),
            'positive_quantity' => filter_var($request->query('positive_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'with' => array_filter(explode(',', $request->query('with', ''))),
        ];

        $stockItems = $this->stockItemService->getFiltered($filters);

        return $this->successResponse(StockItemResource::collection($stockItems));
    }

    /**
     * Create a new stock item.
     */
    public function store(StockItemRequest $request): JsonResponse
    {
        $stockItem = $this->stockItemService->createStockItem($request->validated());

        return $this->successResponse(
            new StockItemResource($stockItem),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific stock item.
     */
    public function show(int $stock_item): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));
        $stockItem = $this->stockItemService->findById($stock_item, $with);

        return $this->successResponse(new StockItemResource($stockItem));
    }

    /**
     * Update a stock item.
     */
    public function update(StockItemRequest $request, int $stock_item): JsonResponse
    {
        $updatedStockItem = $this->stockItemService->updateStockItem($stock_item, $request->validated());

        return $this->successResponse(
            new StockItemResource($updatedStockItem),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a stock item.
     */
    public function destroy(int $stock_item): JsonResponse
    {
        $this->stockItemService->delete($stock_item);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
