<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\Hierarchy;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $this->planService->processRequestParams($request->query());
        $plans = $this->planService->getFiltered($filters);
        $totalCount = $plans->count();

        return ApiResponseMiddleware::listResponse(
            PlanResource::collection($plans),
            'plan',
            $totalCount
        );
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $plan = $this->planService->createPlan($request->validated());

        return ApiResponseMiddleware::createResponse(
            new PlanResource($plan),
            'plan',
            $plan->toArray()
        );
    }

    public function show(int $id): JsonResponse
    {
        $plan = $this->planService->findById($id);
        $plan->loadCount(['licenses', 'organizations']);

        return ApiResponseMiddleware::showResponse(
            new PlanResource($plan),
            'plan',
            $plan->toArray()
        );
    }

    public function update(PlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->planService->updatePlan($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new PlanResource($plan),
            'plan',
            $plan->toArray()
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->planService->deletePlan($id);

        return ApiResponseMiddleware::deleteResponse('plan');
    }
}
