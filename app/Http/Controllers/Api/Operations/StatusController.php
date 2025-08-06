<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Operations;

use App\Constants\ErrorMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StatusController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly StatusService $statusService,
    ) {}

    /**
     * Get all item statuses.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->statusService->processRequestParams($request->query());
        $statusesQuery = $this->statusService->getFiltered($filters);
        $totalCount = $statusesQuery->count();

        $statuses = $this->paginated($statusesQuery, $request);

        return ApiResponseMiddleware::listResponse(
            StatusResource::collection($statuses),
            'status',
            $totalCount
        );
    }

    /**
     * Create a new item status.
     */
    public function store(StatusRequest $request): JsonResponse
    {
        $status = $this->statusService->createStatus($request->validated());

        return ApiResponseMiddleware::createResponse(
            new StatusResource($status),
            'status',
            $status->toArray()
        );
    }

    /**
     * Get a specific item status.
     */
    public function show($id): JsonResponse
    {
        $status = $this->statusService->findById($id);

        return ApiResponseMiddleware::showResponse(
            new StatusResource($status),
            'status',
            $status->toArray()
        );
    }

    /**
     * Update an item status.
     */
    public function update(StatusRequest $request, $id): JsonResponse
    {
        $updatedStatus = $this->statusService->updateStatus($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new StatusResource($updatedStatus),
            'status',
            $updatedStatus->toArray()
        );
    }

    /**
     * Delete item status.
     */
    public function destroy($id): JsonResponse
    {
        $status = $this->statusService->findById($id);

        // Check if item status is in use by items
        if ($status->items()->count() > 0) {
            throw new InvalidArgumentException(__(ErrorMessages::STATUS_IN_USE));
        }

        $this->statusService->delete($id);

        return ApiResponseMiddleware::deleteResponse(
            'status',
            $status->toArray()
        );
    }
}
