<?php

namespace App\Http\Controllers\Api\Operations;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\CheckInOutRequest;
use App\Http\Resources\CheckInOutResource;
use App\Services\CheckInOutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInOutController extends BaseController
{
    public function __construct(
        private CheckInOutService $checkInOutService,
    ) {}

    /**
     * Check out an item.
     */
    public function checkout(CheckInOutRequest $request, string $itemLocationId): JsonResponse
    {
        try {
            $checkInOut = $this->checkInOutService->checkout($itemLocationId, $request->validated());

            return $this->successResponse(
                new CheckInOutResource($checkInOut->load(['user', 'trackable.item', 'trackable.location', 'checkoutLocation', 'statusOut'])),
                SuccessMessages::RESOURCE_CREATED,
                HttpStatus::HTTP_CREATED,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_CONFLICT);
        }
    }

    /**
     * Check in an item.
     */
    public function checkin(CheckInOutRequest $request, string $itemLocationId): JsonResponse
    {
        try {
            $checkInOut = $this->checkInOutService->checkin($itemLocationId, $request->validated());

            return $this->successResponse(
                new CheckInOutResource($checkInOut->load(['user', 'checkinUser', 'trackable.item', 'trackable.location', 'checkinLocation', 'statusIn'])),
                SuccessMessages::RESOURCE_UPDATED,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_CONFLICT);
        }
    }

    /**
     * Get the checkout history for a specific item location.
     */
    public function history(Request $request, string $itemLocationId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $history = $this->checkInOutService->getHistory($itemLocationId, $perPage);

            return $this->successResponse(CheckInOutResource::collection($history));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_CONFLICT);
        }
    }

    /**
     * Get filtered check-in/out records.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'user_id', 'item_id', 'checkout_location_id', 'checkin_location_id',
                'status_out_id', 'status_in_id', 'is_active', 'active_only', 
                'overdue_only', 'date_from', 'date_to'
            ]);
            
            $filters['with'] = ['user', 'trackable.item', 'trackable.location', 'checkoutLocation', 'checkinLocation', 'statusOut', 'statusIn'];
            $checkInOuts = $this->checkInOutService->getFiltered($filters);

            return $this->successResponse(CheckInOutResource::collection($checkInOuts));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
