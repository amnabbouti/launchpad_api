<?php

namespace App\Http\Controllers\Api\Operations;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private StatusService $statusService,
    ) {}

    /**
     * Get all statuses or item statuses.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $this->getValidatedType($request);

        if ($type instanceof JsonResponse) {
            return $type;
        }

        if ($type === 'item-status') {
            $filters = $this->statusService->processItemStatusParams($request->query());
            $statuses = $this->statusService->getItemStatuses($filters);
            $resourceType = 'item statuses';
        } else {
            $filters = $this->statusService->processStatusParams($request->query());
            $statuses = $this->statusService->getFiltered($filters);
            $resourceType = 'statuses';
        }

        // Check if results are empty
        if ($statuses->isEmpty()) {
            $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));

            if ($hasFilters) {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', $resourceType, SuccessMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            // replace the message placeholder with the resource type
            $message = str_replace('Resources', ucfirst($resourceType), SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(StatusResource::collection($statuses), $message);
    }

    /**
     * Create a new status or ItemStatus.
     */
    public function store(StatusRequest $request): JsonResponse
    {
        $type = $this->getValidatedType($request);

        if ($type instanceof JsonResponse) {
            return $type;
        }

        $data = $request->validated();

        if ($type === 'item-status') {
            $status = $this->statusService->createItemStatus($data);
        } else {
            $status = $this->statusService->create($data);
        }

        return $this->successResponse(
            new StatusResource($status),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific status Status or ItemStatus.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $type = $this->getValidatedType($request);

        if ($type === 'item-status') {
            $status = $this->statusService->findItemStatusById($id);
        } else {
            $status = $this->statusService->findById($id);
        }

        return $this->successResponse(new StatusResource($status), SuccessMessages::RESOURCE_RETRIEVED);
    }

    /**
     * Update a Status or ItemStatus.
     */
    public function update(StatusRequest $request, $id): JsonResponse
    {
        $type = $this->getValidatedType($request);
        $data = $request->validated();

        if ($type === 'item-status') {
            $updatedStatus = $this->statusService->updateItemStatus($id, $data);
        } else {
            $updatedStatus = $this->statusService->updateStatus($id, $data);
        }

        return $this->successResponse(
            new StatusResource($updatedStatus),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a Status or ItemStatus.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $type = $this->getValidatedType($request);

        if ($type === 'item-status') {
            $status = $this->statusService->findItemStatusById($id);

            // Check if item status is in use
            if ($status->stockItems()->count() > 0
                || $status->maintenancesOut()->count() > 0
                || $status->maintenancesIn()->count() > 0
                || $status->checkouts()->count() > 0
                || $status->checkins()->count() > 0) {
                return $this->errorResponse(
                    ErrorMessages::RESOURCE_IN_USE,
                    HttpStatus::HTTP_CONFLICT,
                );
            }

            $this->statusService->deleteItemStatus($id);
        } else {
            $status = $this->statusService->findById($id);

            // Check if status is in use
            if ($status->stocks()->count() > 0
                || $status->maintenancesOut()->count() > 0
                || $status->maintenancesIn()->count() > 0
                || $status->checkInOutsOut()->count() > 0
                || $status->checkInOutsIn()->count() > 0) {
                return $this->errorResponse(
                    ErrorMessages::RESOURCE_IN_USE,
                    HttpStatus::HTTP_CONFLICT,
                );
            }

            $this->statusService->delete($id);
        }

        return $this->successResponse(null, SuccessMessages::RESOURCE_DELETED);
    }

    /**
     * Validate type parameter.
     */
    private function getValidatedType(Request $request): string
    {
        $type = $request->query('type', 'status');

        if (! in_array($type, ['status', 'item-status'])) {
            throw new \InvalidArgumentException("Invalid type parameter: '{$type}'. Allowed values are: 'status', 'item-status'");
        }

        return $type;
    }
}
