<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends BaseController {
    public function __construct(
        private ItemService $itemService,
    ) {}

    /**
     * Delete an item.
     */
    public function destroy($id): JsonResponse {
        $item = $this->itemService->findById($id);
        $this->itemService->delete($id);

        return ApiResponseMiddleware::deleteResponse('item', $item->toArray());
    }

    /**
     * Get items with optional filters.
     */
    public function index(Request $request): JsonResponse {
        $filters = $this->itemService->processRequestParams($request->query());
        $query   = $this->itemService->getFiltered($filters);
        $items   = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemResource::collection($items),
            'item',
            $items->total(),
        );
    }

    /**
     * Get a specific item.
     */
    public function show($id): JsonResponse {
        $item = $this->itemService->findById($id);

        return ApiResponseMiddleware::showResponse(
            new ItemResource($item),
            'item',
            $item->toArray(),
        );
    }

    /**
     * Create a new item.
     */
    public function store(ItemRequest $request): JsonResponse {
        $item = $this->itemService->create($request->validated());

        return ApiResponseMiddleware::createResponse(
            new ItemResource($item),
            'item',
            $item->toArray(),
        );
    }

    /**
     * Update an item.
     */
    public function update(ItemRequest $request, $id): JsonResponse {
        $updatedItem = $this->itemService->update($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new ItemResource($updatedItem),
            'item',
            $updatedItem->toArray(),
        );
    }
}
