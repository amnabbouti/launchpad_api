<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\MaintenanceDetailRequest;
use App\Http\Resources\MaintenanceDetailResource;
use App\Services\MaintenanceDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceDetailController extends BaseController {
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly MaintenanceDetailService $maintenanceDetailService,
    ) {}

    /**
     * Remove the specified maintenance detail.
     */
    public function destroy(string $id): JsonResponse {
        $maintenanceDetail = $this->maintenanceDetailService->findById($id);
        $this->maintenanceDetailService->delete($id);

        return ApiResponseMiddleware::deleteResponse(
            'maintenance_detail',
            $maintenanceDetail->toArray(),
        );
    }

    /**
     * Display a listing of maintenance details.
     */
    public function index(Request $request): JsonResponse {
        $params = $this->maintenanceDetailService->processRequestParams($request->query());

        $maintenanceDetailsQuery = $this->maintenanceDetailService->getFiltered($params);
        $totalCount              = $maintenanceDetailsQuery->count();

        $maintenanceDetails = $this->paginated($maintenanceDetailsQuery, $request);

        return ApiResponseMiddleware::listResponse(
            MaintenanceDetailResource::collection($maintenanceDetails),
            'maintenance_detail',
            $totalCount,
        );
    }

    /**
     * Display the specified maintenance detail.
     */
    public function show(Request $request, string $id): JsonResponse {
        $with = $request->query('with') ? explode(',', $request->query('with')) : ['maintenance', 'maintenanceCondition'];

        $maintenanceDetail = $this->maintenanceDetailService->findById($id, ['*'], $with);

        return ApiResponseMiddleware::showResponse(
            new MaintenanceDetailResource($maintenanceDetail),
            'maintenance_detail',
            $maintenanceDetail->toArray(),
        );
    }

    /**
     * Store a newly created maintenance detail.
     */
    public function store(MaintenanceDetailRequest $request): JsonResponse {
        $maintenanceDetail = $this->maintenanceDetailService->createMaintenanceDetail($request->validated());

        return ApiResponseMiddleware::createResponse(
            new MaintenanceDetailResource($maintenanceDetail),
            'maintenance_detail',
            $maintenanceDetail->toArray(),
        );
    }

    /**
     * Update the specified maintenance detail.
     */
    public function update(MaintenanceDetailRequest $request, string $id): JsonResponse {
        $updatedMaintenanceDetail = $this->maintenanceDetailService->updateMaintenanceDetail($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new MaintenanceDetailResource($updatedMaintenanceDetail),
            'maintenance_detail',
            $updatedMaintenanceDetail->toArray(),
        );
    }
}
