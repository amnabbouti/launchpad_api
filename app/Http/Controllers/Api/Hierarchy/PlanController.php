<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Constants\SuccessMessages;
use App\Constants\HttpStatus;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\PlanResource;
use App\Http\Requests\PlanRequest;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanController extends BaseController
{
    private PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $this->planService->processRequestParams($request->query());
        $plans = $this->planService->getFiltered($filters);
        return $this->successResponse(PlanResource::collection($plans));
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $plan = $this->planService->createPlan($request->validated());
        return $this->successResponse(
            new PlanResource($plan),
            SuccessMessages::created('Plan'),
            HttpStatus::HTTP_CREATED,
        );
    }

    public function show(int $id): JsonResponse
    {
        $plan = $this->planService->findById($id);
        $plan->loadCount(['licenses', 'organizations']);
        return $this->successResponse(new PlanResource($plan));
    }

    public function update(PlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->planService->updatePlan($id, $request->validated());
        return $this->successResponse(
            new PlanResource($plan),
            SuccessMessages::updated('Plan'),
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->planService->deletePlan($id);
        return $this->successResponse(null, SuccessMessages::deleted('Plan'));
    }
}
