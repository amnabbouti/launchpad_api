<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\ItemMoveRequest;
use App\Http\Resources\ItemMovementResource;
use App\Services\ItemMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemMovementController extends BaseController
{
    public function __construct(
        private ItemMovementService $movementService,
    ) {}

    /**
     * Move an item between locations.
     */
    public function move(ItemMoveRequest $request): JsonResponse
    {
        $result = $this->movementService->moveItem($request->validated());

        return ApiResponseMiddleware::success([
            'movement_id' => $result['movement_id'],
            'movement_type' => $result['movement_type'],
        ], $result['message']);
    }

    /**
     * Perform initial placement of an item.
     */
    public function initialPlacement(ItemMoveRequest $request): JsonResponse
    {
        $result = $this->movementService->initialPlacement($request->validated());

        return ApiResponseMiddleware::success([
            'movement_id' => $result['movement_id'],
            'movement_type' => $result['movement_type'],
        ], $result['message']);
    }

    /**
     * Adjust item quantity at a location.
     */
    public function adjustQuantity(ItemMoveRequest $request): JsonResponse
    {
        $result = $this->movementService->adjustQuantity($request->validated());

        return ApiResponseMiddleware::success([
            'movement_id' => $result['movement_id'],
            'movement_type' => $result['movement_type'],
        ], $result['message']);
    }

    /**
     * Get movement history for an item.
     */
    public function history(Request $request, string $itemId): JsonResponse
    {
        $filters = $request->only(['movement_type', 'date_from', 'date_to', 'limit']);
        $history = $this->movementService->getMovementHistory($itemId, $filters);

        return ApiResponseMiddleware::listResponse(
            ItemMovementResource::collection(collect($history['movements'])),
            'movements',
            $history['total']
        );
    }

    /**
     * Validate and repair item location integrity.
     */
    public function validateIntegrity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'nullable|string',
            'auto_repair' => 'boolean',
        ]);

        $result = $this->movementService->validateAndRepairItemLocationIntegrity(
            $validated['item_id'] ?? null,
            $validated['auto_repair'] ?? false
        );

        return ApiResponseMiddleware::success($result, 'Integrity validation completed');
    }
}
