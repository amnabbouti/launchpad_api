<?php

namespace App\Http\Controllers\Api\Stock;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends BaseController
{
    public function __construct(
        private ItemService $itemService,
    ) {}

    /**
     * Get items with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->itemService->processRequestParams($request->query());
        $items = $this->itemService->getFiltered($filters);

        // Determine appropriate message
        $message = $items->isEmpty()
            ? 'No items found'
            : SuccessMessages::RESOURCES_RETRIEVED;

        return $this->successResponse(
            ItemResource::collection($items),
            $message
        );
    }

    /**
     * Create a new item.
     */
    public function store(ItemRequest $request): JsonResponse
    {
        $item = $this->itemService->create($request->validated());

        return $this->successResponse(
            new ItemResource($item),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific item.
     */
    public function show($id): JsonResponse
    {
        $item = $this->itemService->findById($id);

        return $this->successResponse(new ItemResource($item));
    }

    /**
     * Update an item.
     */
    public function update(ItemRequest $request, $id): JsonResponse
    {
        $updatedItem = $this->itemService->update($id, $request->validated());

        return $this->successResponse(
            new ItemResource($updatedItem),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete an item.
     */
    public function destroy($id): JsonResponse
    {
        $this->itemService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }

    /**
     * Toggle item maintenance status.
     */
    public function toggleMaintenance(Request $request, $id): JsonResponse
    {
        $action = $request->input('action'); // 'in' or 'out'
        $date = $request->input('date', now()->toISOString());
        $remarks = $request->input('remarks');
        $isRepair = $request->boolean('is_repair', false);

        $item = $this->itemService->toggleMaintenance($id, $action, $date, $remarks, $isRepair);

        return $this->successResponse(
            new ItemResource($item),
            $action === 'in' ? 'Item sent to maintenance' : 'Item returned from maintenance'
        );
    }
}
