<?php

namespace App\Http\Controllers\Api\Stock;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StockRequest;
use App\Http\Resources\StockResource;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends BaseController
{
    public function __construct(
        private StockService $stockService,
    ) {}

    /**
     * Display a listing of stocks for the authenticated user's organization.
     */
    public function index(Request $request): JsonResponse
    {
        $rawParams = $request->query();
        $processed = $this->stockService->processRequestParams($rawParams);
        $stocks = $this->stockService->getFiltered($processed);

        return $this->successResponse(StockResource::collection($stocks));
    }

    /**
     * Store a newly created stock.
     */
    public function store(StockRequest $request): JsonResponse
    {
        $stock = $this->stockService->createStock($request->validated());

        return $this->successResponse(
            new StockResource($stock),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Display the specified stock.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $with = $request->query('with', '') ? explode(',', $request->query('with')) : [];
        $stock = $this->stockService->findById($id, ['*'], $with);

        return $this->successResponse(new StockResource($stock));
    }

    /**
     * Update the specified stock.
     */
    public function update(StockRequest $request, int $id): JsonResponse
    {
        $updatedStock = $this->stockService->updateStock($id, $request->validated());

        return $this->successResponse(
            new StockResource($updatedStock),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Remove the specified stock.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->stockService->deleteStock($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
