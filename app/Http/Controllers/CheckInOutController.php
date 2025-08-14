<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\CheckInOutRequest;
use App\Http\Resources\CheckInOutResource;
use App\Services\CheckInOutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInOutController extends BaseController {
    public function __construct(
        private readonly CheckInOutService $checkInOutService,
    ) {}

    /**
     * Check availability for item location.
     */
    public function checkAvailability($itemLocationId): JsonResponse {
        $availability = $this->checkInOutService->getItemLocationAvailability($itemLocationId);

        return ApiResponseMiddleware::success(
            $availability,
            'succ.checkinout.availability',
        );
    }

    /**
     * Checkin method for item location.
     */
    public function checkin(CheckInOutRequest $request, $itemLocationId): JsonResponse {
        $updatedCheckInOut = $this->checkInOutService->processItemLocationCheckin(
            $itemLocationId,
            $request->validated(),
        );

        return ApiResponseMiddleware::updateResponse(
            new CheckInOutResource($updatedCheckInOut),
            'checkinout',
            $updatedCheckInOut->toArray(),
        );
    }

    /**
     * Checkout method for item location.
     */
    public function checkout(CheckInOutRequest $request, $itemLocationId): JsonResponse {
        $checkInOut = $this->checkInOutService->processItemLocationCheckout(
            $itemLocationId,
            $request->validated(),
        );

        return ApiResponseMiddleware::createResponse(
            new CheckInOutResource($checkInOut),
            'checkinout',
            $checkInOut->toArray(),
        );
    }

    /**
     * Delete a check-in/out record.
     */
    public function destroy($id): JsonResponse {
        $checkInOut = $this->checkInOutService->findById($id);
        $this->checkInOutService->delete($id);

        return ApiResponseMiddleware::deleteResponse(
            'checkinout',
            $checkInOut->toArray(),
        );
    }

    /**
     * Get history for item location.
     */
    public function history($itemLocationId): JsonResponse {
        $history    = $this->checkInOutService->getItemLocationHistory($itemLocationId);
        $totalCount = $history->count();

        return ApiResponseMiddleware::listResponse(
            CheckInOutResource::collection($history),
            'checkinout',
            $totalCount,
        );
    }

    /**
     * Get all check-in/out records.
     */
    public function index(Request $request): JsonResponse {
        $filters     = $this->checkInOutService->processRequestParams($request->query());
        $query       = $this->checkInOutService->getFiltered($filters);
        $checkInOuts = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            CheckInOutResource::collection($checkInOuts),
            'checkinout',
            $checkInOuts->total(),
        );
    }

    /**
     * Get a specific check-in/out record.
     */
    public function show($id): JsonResponse {
        $checkInOut = $this->checkInOutService->findByIdWithRelations($id);

        return ApiResponseMiddleware::showResponse(
            new CheckInOutResource($checkInOut),
            'checkinout',
            $checkInOut->toArray(),
        );
    }

    /**
     * Create a new check-in/out record.
     */
    public function store(CheckInOutRequest $request): JsonResponse {
        // Use the event-enabled method based on the operation type
        $data = $request->validated();

        // If this is a checkout operation (has checkout_date but no checkin_date)
        if (isset($data['checkout_date']) && ! isset($data['checkin_date'])) {
            $checkInOut = $this->checkInOutService->processItemCheckout($data);
        }
        // If this is a checkin operation (has checkin_date)
        elseif (isset($data['checkin_date'])) {
            $checkInOut = $this->checkInOutService->createCheckin($data);
        }
        // Default fallback (shouldn't normally happen with proper validation)
        else {
            $checkInOut = $this->checkInOutService->create($data);
        }

        return ApiResponseMiddleware::createResponse(
            new CheckInOutResource($checkInOut),
            'checkinout',
            $checkInOut->toArray(),
        );
    }

    /**
     * Update a check-in/out record.
     */
    public function update(CheckInOutRequest $request, $id): JsonResponse {
        $data = $request->validated();

        // If this update includes checkin_date, it's likely a checkin operation
        if (isset($data['checkin_date'])) {
            $updatedCheckInOut = $this->checkInOutService->processItemCheckin($id, $data);
        }
        // Otherwise use regular update
        else {
            $updatedCheckInOut = $this->checkInOutService->update($id, $data);
        }

        return ApiResponseMiddleware::updateResponse(
            new CheckInOutResource($updatedCheckInOut),
            'checkinout',
            $updatedCheckInOut->toArray(),
        );
    }
}
