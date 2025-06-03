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
     * Check out a stock item.
     */
    public function checkout(CheckInOutRequest $request, int $stockItemId): JsonResponse
    {
        try {
            $checkInOut = $this->checkInOutService->checkout($stockItemId, $request->validated());

            return $this->successResponse(
                new CheckInOutResource($checkInOut->load(['user', 'checkoutLocation', 'statusOut'])),
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
     * Check in a stock item.
     */
    public function checkin(CheckInOutRequest $request, int $stockItemId): JsonResponse
    {
        try {
            $checkInOut = $this->checkInOutService->checkin($stockItemId, $request->validated());

            return $this->successResponse(
                new CheckInOutResource($checkInOut->load(['user', 'checkinUser', 'checkinLocation', 'statusIn'])),
                SuccessMessages::RESOURCE_UPDATED,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_CONFLICT);
        }
    }

    /**
     * Get the checkout history for a specific stock item.
     */
    public function history(Request $request, int $stockItemId): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $history = $this->checkInOutService->getHistory($stockItemId, $perPage);

            return $this->successResponse(CheckInOutResource::collection($history));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_CONFLICT);
        }
    }
}
