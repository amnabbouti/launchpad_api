<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ThreatDetectionRequest;
use App\Http\Resources\ThreatDetectionResource;
use App\Services\ThreatDetectionService;
use Illuminate\Http\JsonResponse;

class ThreatDetectionController extends BaseController
{
    protected ThreatDetectionService $threatDetectionService;

    public function __construct(ThreatDetectionService $threatDetectionService)
    {
        $this->threatDetectionService = $threatDetectionService;
    }

    /**
     * Get comprehensive threat detection overview
     */
    public function overview(ThreatDetectionRequest $request): JsonResponse
    {
        $organizationId = $request->user()->isSuperAdmin() ? null : $request->user()->org_id;

        $threatData = $this->threatDetectionService->getThreatOverview($organizationId);

        return $this->successResponse(
            new ThreatDetectionResource($threatData),
            'Threat detection overview retrieved successfully'
        );
    }
}
