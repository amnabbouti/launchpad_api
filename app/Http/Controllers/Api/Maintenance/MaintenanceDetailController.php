<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\MaintenanceDetailRequest;
use App\Http\Resources\MaintenanceDetailResource;
use App\Services\MaintenanceDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceDetailController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private MaintenanceDetailService $maintenanceDetailService,
    ) {}

    /**
     * Display a listing of maintenance details.
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->maintenanceDetailService->processRequestParams($request->query());

        $maintenanceDetails = $this->maintenanceDetailService->getFiltered($params);

        return $this->successResponse(MaintenanceDetailResource::collection($maintenanceDetails));
    }

    /**
     * Store a newly created maintenance detail.
     */
    public function store(MaintenanceDetailRequest $request): JsonResponse
    {
        $maintenanceDetail = $this->maintenanceDetailService->createMaintenanceDetail($request->validated());

        return $this->successResponse(
            new MaintenanceDetailResource($maintenanceDetail),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Display the specified maintenance detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $with = $request->query('with') ? explode(',', $request->query('with')) : ['maintenance', 'maintenanceCondition'];

        $maintenanceDetail = $this->maintenanceDetailService->findById($id, $with);

        if (! $maintenanceDetail) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        return $this->successResponse(new MaintenanceDetailResource($maintenanceDetail));
    }

    /**
     * Update the specified maintenance detail.
     */
    public function update(MaintenanceDetailRequest $request, int $id): JsonResponse
    {
        $maintenanceDetail = $this->maintenanceDetailService->findById($id);

        if (! $maintenanceDetail) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        $updatedMaintenanceDetail = $this->maintenanceDetailService->updateMaintenanceDetail($id, $request->validated());

        return $this->successResponse(
            new MaintenanceDetailResource($updatedMaintenanceDetail),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Remove the specified maintenance detail.
     */
    public function destroy(int $id): JsonResponse
    {
        $maintenanceDetail = $this->maintenanceDetailService->findById($id);

        if (! $maintenanceDetail) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        $this->maintenanceDetailService->delete($id);

        return $this->successResponse(
            null,
            SuccessMessages::RESOURCE_DELETED,
            HttpStatus::HTTP_NO_CONTENT,
        );
    }
}
