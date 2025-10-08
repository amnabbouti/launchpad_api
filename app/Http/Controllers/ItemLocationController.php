<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\ItemLocationRequest;
use App\Http\Resources\ItemLocationResource;
use App\Services\ItemLocationService;
use Illuminate\Http\JsonResponse;

class ItemLocationController extends BaseController {
    protected ItemLocationService $itemLocationService;

    public function __construct(ItemLocationService $itemLocationService) {
        $this->itemLocationService = $itemLocationService;
    }

    /**
     * Remove an item from a location.
     */
    public function destroy(string $id): JsonResponse {
        $itemLocation = $this->itemLocationService->findById($id);
        $this->itemLocationService->deleteItemLocation($id);

        return ApiResponseMiddleware::deleteResponse('item_location', $itemLocation->toArray());
    }

    /**
     * Get item locations with optional filters.
     */
    public function index(): JsonResponse {
        $request = request();
        $filters = [
            'location_id'       => (int) ($request->query('location_id')) ?: null,
            'item_id'           => (int) ($request->query('item_id')) ?: null,
            'moved_date'        => $request->query('moved_date'),
            'positive_quantity' => filter_var($request->query('positive_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'with'              => array_filter(explode(',', $request->query('with', ''))),
        ];

        $query         = $this->itemLocationService->getFiltered($filters);
        $itemLocations = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemLocationResource::collection($itemLocations),
            'item_location',
            $itemLocations->total(),
        );
    }

    /**
     * Get a specific item location.
     */
    public function show(string $id): JsonResponse {
        $with         = array_filter(explode(',', request()->query('with', '')));
        $itemLocation = $this->itemLocationService->findById($id, $with);

        return ApiResponseMiddleware::showResponse(
            new ItemLocationResource($itemLocation),
            'item_location',
            $itemLocation->toArray(),
        );
    }

    /**
     * Create a new item location.
     */
    public function store(ItemLocationRequest $request): JsonResponse {
        $itemLocation = $this->itemLocationService->createItemLocation($request->validated());

        return ApiResponseMiddleware::createResponse(
            new ItemLocationResource($itemLocation),
            'item_location',
            $itemLocation->toArray(),
        );
    }

    /**
     * Update an item location.
     */
    public function update(ItemLocationRequest $request, string $id): JsonResponse {
        $updatedItemLocation = $this->itemLocationService->updateItemLocation($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new ItemLocationResource($updatedItemLocation),
            'item_location',
            $updatedItemLocation->toArray(),
        );
    }
}
