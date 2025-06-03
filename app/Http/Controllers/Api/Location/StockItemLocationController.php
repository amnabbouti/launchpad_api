<?php

namespace App\Http\Controllers\Api\Location;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StockItemLocationRequest;
use App\Http\Resources\StockItemLocationResource;
use App\Services\StockItemLocationService;
use Illuminate\Http\JsonResponse;

class StockItemLocationController extends BaseController
{
    protected StockItemLocationService $stockItemLocationService;

    public function __construct(StockItemLocationService $stockItemLocationService)
    {
        $this->stockItemLocationService = $stockItemLocationService;
    }

    /**
     * Get stock item locations with optional filters.
     */
    public function index(): JsonResponse
    {
        $request = request();
        $filters = [
            'location_id' => $request->query('location_id', null, 'intval'),
            'stock_item_id' => $request->query('stock_item_id', null, 'intval'),
            'moved_date' => $request->query('moved_date'),
            'positive_quantity' => filter_var($request->query('positive_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'with' => array_filter(explode(',', $request->query('with', ''))),
        ];

        $stockItemLocations = $this->stockItemLocationService->getFiltered($filters);

        return $this->successResponse(StockItemLocationResource::collection($stockItemLocations));
    }

    /**
     * Create a new stock item location.
     */
    public function store(StockItemLocationRequest $request): JsonResponse
    {
        $stockItemLocation = $this->stockItemLocationService->createStockItemLocation($request->validated());

        return $this->successResponse(
            new StockItemLocationResource($stockItemLocation),
            'Stock item location created successfully',
            self::HTTP_CREATED,
        );
    }

    /**
     * Get a specific stock item location.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));
        $stockItemLocation = $this->stockItemLocationService->findById($id, $with);

        return $this->successResponse(new StockItemLocationResource($stockItemLocation));
    }

    /**
     * Update a stock item location.
     */
    public function update(StockItemLocationRequest $request, int $id): JsonResponse
    {
        $updatedStockItemLocation = $this->stockItemLocationService->updateStockItemLocation($id, $request->validated());

        return $this->successResponse(
            new StockItemLocationResource($updatedStockItemLocation),
            'Stock item location updated successfully',
        );
    }

    /**
     * Move stock items between locations.
     */
    public function moveStockItem(StockItemLocationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $success = $this->stockItemLocationService->moveStockItem(
                $validated['stock_item_id'],
                $validated['from_location_id'],
                $validated['to_location_id'],
                $validated['quantity'],
            );

            if (! $success) {
                return $this->errorResponse(
                    'Not enough quantity available at the source location',
                    self::HTTP_BAD_REQUEST,
                );
            }

            return $this->successResponse(null, 'Stock item moved successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), self::HTTP_BAD_REQUEST);
        }
    }
}
