<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Location;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\ItemLocationRequest;
use App\Http\Resources\ItemLocationResource;
use App\Services\ItemLocationService;
use Illuminate\Http\JsonResponse;

class ItemLocationController extends BaseController
{
    protected ItemLocationService $itemLocationService;

    public function __construct(ItemLocationService $itemLocationService)
    {
        $this->itemLocationService = $itemLocationService;
    }

    /**
     * Get item locations with optional filters.
     */
    public function index(): JsonResponse
    {
        $request = request();
        $filters = [
            'location_id' => intval($request->query('location_id')) ?: null,
            'item_id' => intval($request->query('item_id')) ?: null,
            'moved_date' => $request->query('moved_date'),
            'positive_quantity' => filter_var($request->query('positive_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'with' => array_filter(explode(',', $request->query('with', ''))),
        ];

        $query = $this->itemLocationService->getFiltered($filters);
        $itemLocations = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemLocationResource::collection($itemLocations),
            'item_location',
            $itemLocations->total()
        );
    }

    /**
     * Create a new item location.
     */
    public function store(ItemLocationRequest $request): JsonResponse
    {
        $itemLocation = $this->itemLocationService->createItemLocation($request->validated());

        return ApiResponseMiddleware::createResponse(
            new ItemLocationResource($itemLocation),
            'item_location',
            $itemLocation->toArray()
        );
    }

    /**
     * Get a specific item location.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));
        $itemLocation = $this->itemLocationService->findById($id, $with);

        return ApiResponseMiddleware::showResponse(
            new ItemLocationResource($itemLocation),
            'item_location',
            $itemLocation->toArray()
        );
    }

    /**
     * Update an item location.
     */
    public function update(ItemLocationRequest $request, int $id): JsonResponse
    {
        $updatedItemLocation = $this->itemLocationService->updateItemLocation($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new ItemLocationResource($updatedItemLocation),
            'item_location',
            $updatedItemLocation->toArray()
        );
    }

    /**
     * Remove an item from a location.
     */
    public function destroy(string $id): JsonResponse
    {
        $itemLocation = $this->itemLocationService->findById($id);
        $this->itemLocationService->deleteItemLocation($id);

        return ApiResponseMiddleware::deleteResponse('item_location', $itemLocation->toArray());
    }
}
