<?php

namespace App\Http\Controllers\Api\Operations;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\CheckInOutRequest;
use App\Http\Resources\CheckInOutResource;
use App\Services\CheckInOutService;
use Illuminate\Http\JsonResponse;

class CheckInOutController extends BaseController
{
    public function __construct(
        private CheckInOutService $checkInOutService,
    ) {}

    // All
    public function index(CheckInOutRequest $request): JsonResponse
    {
        $filters = $this->checkInOutService->processRequestParams($request->query());

        // Add default relationships if not specified
        if (!isset($filters['with'])) {
            $filters['with'] = ['user', 'trackable', 'checkoutLocation', 'statusOut'];
        }

        $checkInOuts = $this->checkInOutService->getFiltered($filters);
        $resourceType = 'check-in/out records';

        // Check if results are empty
        if ($checkInOuts->isEmpty()) {
            $hasFilters = !empty(array_filter($filters, fn($value) => $value !== null && $value !== ''));

            if ($hasFilters) {
                $message = str_replace('resources', $resourceType, ErrorMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', $resourceType, ErrorMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            $message = str_replace('Resources', ucfirst($resourceType), SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(CheckInOutResource::collection($checkInOuts), $message);
    }

    // Show
    public function show($id): JsonResponse
    {
        $checkInOut = $this->checkInOutService->findByIdWithRelations($id);

        return $this->successResponse(new CheckInOutResource($checkInOut));
    }

    // Create
    public function store(CheckInOutRequest $request): JsonResponse
    {
        $checkInOut = $this->checkInOutService->create($request->validated());

        return $this->successResponse(
            new CheckInOutResource($checkInOut),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    // Update
    public function update(CheckInOutRequest $request, $id): JsonResponse
    {
        $updatedCheckInOut = $this->checkInOutService->update($id, $request->validated());

        return $this->successResponse(
            new CheckInOutResource($updatedCheckInOut),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    // Delete
    public function destroy($id): JsonResponse
    {
        $this->checkInOutService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }

    // backward compatibility
    public function checkout(CheckInOutRequest $request, $itemLocationId): JsonResponse
    {
        $itemLocationService = app(\App\Services\ItemLocationService::class);
        
        try {
            $itemLocation = $itemLocationService->findById($itemLocationId);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Item location not found',
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        $data = array_merge($request->validated(), [
            'trackable_id' => $itemLocation->id, 
            'trackable_type' => \App\Models\ItemLocation::class,
            'checkout_location_id' => $itemLocation->location_id,
            'checkout_date' => now(),
            'user_id' => auth()->id(),
            'is_active' => true,
        ]);

        $checkInOut = $this->checkInOutService->create($data);

        return $this->successResponse(
            new CheckInOutResource($checkInOut),
            'Item checked out successfully',
            HttpStatus::HTTP_CREATED,
        );
    }

    public function checkin(CheckInOutRequest $request, $itemLocationId): JsonResponse
    {
        $itemLocationService = app(\App\Services\ItemLocationService::class);
        $itemService = app(\App\Services\ItemService::class);
        
        $itemLocation = null;
        
        // Try to find as ItemLocation first
        try {
            $itemLocation = $itemLocationService->findById($itemLocationId);
        } catch (\Exception $e) {
            // try to find as Item and get its ItemLocation
            try {
                $item = $itemService->findById($itemLocationId);
                
                // Get the first ItemLocation for this item that has active checkouts
                $itemLocations = $item->itemLocations()->get();
                
                foreach ($itemLocations as $il) {
                    $activeCheckouts = $this->checkInOutService->getFiltered([
                        'trackable_id' => $il->id,
                        'trackable_type' => \App\Models\ItemLocation::class,
                        'user_id' => auth()->id(),
                        'is_checked_out' => true,
                    ]);
                    
                    if ($activeCheckouts->isNotEmpty()) {
                        $itemLocation = $il;
                        break;
                    }
                }
                
                if (!$itemLocation) {
                    return $this->errorResponse(
                        'No active checkout found for this item',
                        HttpStatus::HTTP_NOT_FOUND
                    );
                }
            } catch (\Exception $e) {
                return $this->errorResponse(
                    'Item or item location not found',
                    HttpStatus::HTTP_NOT_FOUND
                );
            }
        }

        // Find active checkout for this item location and user
        $activeCheckout = $this->checkInOutService->getFiltered([
            'trackable_id' => $itemLocation->id,
            'trackable_type' => \App\Models\ItemLocation::class,
            'user_id' => auth()->id(),
            'is_checked_out' => true,
        ])->first();

        if (!$activeCheckout) {
            return $this->errorResponse(
                'No active checkout found for this item',
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        $data = array_merge($request->validated(), [
            'checkin_date' => now(),
            'checkin_user_id' => auth()->id(),
            'is_active' => false,
        ]);

        $updatedCheckInOut = $this->checkInOutService->update($activeCheckout->id, $data);

        return $this->successResponse(
            new CheckInOutResource($updatedCheckInOut),
            'Item checked in successfully'
        );
    }

    public function checkAvailability($itemLocationId): JsonResponse
    {
        $availability = $this->checkInOutService->getAvailabilityData($itemLocationId);

        return $this->successResponse($availability, 'Availability data retrieved');
    }

    public function history($itemLocationId): JsonResponse
    {
        $history = $this->checkInOutService->getFiltered([
            'trackable_id' => $itemLocationId,
            'trackable_type' => \App\Models\ItemLocation::class,
            'with' => ['user', 'checkinUser', 'checkoutLocation', 'checkinLocation', 'statusOut', 'statusIn']
        ]);

        return $this->successResponse(
            CheckInOutResource::collection($history),
            'Check-in/out history retrieved'
        );
    }
}
