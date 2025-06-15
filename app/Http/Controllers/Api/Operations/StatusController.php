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
     * Get all item statuses.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->statusService->processRequestParams($request->query());
        $statuses = $this->statusService->getFiltered($filters);

        // Check if results are empty
        if ($statuses->isEmpty()) {
            $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));

            if ($hasFilters) {
                $message = str_replace('resources', 'item statuses', ErrorMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', 'item statuses', ErrorMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            $message = str_replace('Resources', 'Item statuses', SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(StatusResource::collection($statuses), $message);
    }

    /**
     * Create a new item status.
     */
    public function store(StatusRequest $request): JsonResponse
    {
        $data = $request->validated();
        $status = $this->statusService->create($data);

        return $this->successResponse(
            new StatusResource($status),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific item status.
     */
    public function show($id): JsonResponse
    {
        $status = $this->statusService->findById($id);

        return $this->successResponse(new StatusResource($status), SuccessMessages::RESOURCE_RETRIEVED);
    }

    /**
     * Update an item status.
     */
    public function update(StatusRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $updatedStatus = $this->statusService->update($id, $data);

        return $this->successResponse(
            new StatusResource($updatedStatus),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete an item status.
     */
    public function destroy($id): JsonResponse
    {
        $status = $this->statusService->findById($id);

        // Check if item status is in use by items
        if ($status->items()->count() > 0) {
            return $this->errorResponse(
                ErrorMessages::RESOURCE_IN_USE,
                HttpStatus::HTTP_CONFLICT,
            );
        }

        $this->statusService->delete($id);

        return $this->successResponse(null, SuccessMessages::RESOURCE_DELETED);
    }
}
