<?php

namespace App\Http\Controllers\Api\Stock;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;

class ItemController extends BaseController
{
    public function __construct(
        private ItemService $itemService,
    ) {}

    /**
     * Get items with optional filters.
     */
    public function index(ItemRequest $request): JsonResponse
    {
        $filters = $this->itemService->processRequestParams($request->query());
        $items = $this->itemService->getFiltered($filters);
        $resourceType = 'items';

        // Check if results are empty
        if ($items->isEmpty()) {
            $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));

            if ($hasFilters) {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            $message = str_replace('Resources', ucfirst($resourceType), SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(ItemResource::collection($items), $message);
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
        $with = [];
        if (request()->has('with')) {
            $with = array_filter(explode(',', request()->query('with')));
        }
        $item = $this->itemService->findById($id, ['*'], $with);

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
}
