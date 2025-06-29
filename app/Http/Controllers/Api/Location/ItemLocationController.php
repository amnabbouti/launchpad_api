<?php

namespace App\Http\Controllers\Api\Location;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
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
     * Get stock item locations with optional filters.
     */
    public function index(): JsonResponse
    {
        $request = request();
        $filters = [
            'location_id' => $request->query('location_id', null, 'intval'),
            'item_id' => $request->query('item_id', null, 'intval'),
            'moved_date' => $request->query('moved_date'),
            'positive_quantity' => filter_var($request->query('positive_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'with' => array_filter(explode(',', $request->query('with', ''))),
        ];

        $itemLocations = $this->itemLocationService->getFiltered($filters);
        $message = str_replace('Resources', 'Item locations', SuccessMessages::RESOURCES_RETRIEVED);

        return $this->successResponse(ItemLocationResource::collection($itemLocations), $message);
    }

    /**
     * Create a new item location.
     */
    public function store(ItemLocationRequest $request): JsonResponse
    {
        $itemLocation = $this->itemLocationService->createItemLocation($request->validated());

        return $this->successResponse(
            new ItemLocationResource($itemLocation),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific item location.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));
        $itemLocation = $this->itemLocationService->findById($id, $with);

        return $this->successResponse(
            new ItemLocationResource($itemLocation),
            str_replace('Resource', 'Item location', SuccessMessages::RESOURCE_RETRIEVED)
        );
    }

    /**
     * Update an item location.
     */
    public function update(ItemLocationRequest $request, int $id): JsonResponse
    {
        $updatedItemLocation = $this->itemLocationService->updateItemLocation($id, $request->validated());

        return $this->successResponse(
            new ItemLocationResource($updatedItemLocation),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Move items between locations.
     */
    public function moveItem(ItemLocationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $success = $this->itemLocationService->moveItem(
                $validated['item_id'],
                $validated['from_location_id'] ?? null,
                $validated['to_location_id'],
                $validated['quantity'],
            );

            if (! $success) {
                return $this->errorResponse(
                    'Not enough quantity available at the source location',
                    HttpStatus::HTTP_BAD_REQUEST,
                );
            }

            return $this->successResponse(null, SuccessMessages::ITEM_MOVED);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remove an item from a location.
     */
    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->itemLocationService->deleteItemLocation($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
