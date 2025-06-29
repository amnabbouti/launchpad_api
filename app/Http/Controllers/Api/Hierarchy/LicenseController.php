<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Constants\SuccessMessages;
use App\Constants\HttpStatus;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\LicenseResource;
use App\Http\Requests\LicenseRequest;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LicenseController extends BaseController
{
    private LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $this->licenseService->processRequestParams($request->query());
        $licenses = $this->licenseService->getFiltered($filters);
        return $this->successResponse(LicenseResource::collection($licenses));
    }

    public function store(LicenseRequest $request): JsonResponse
    {
        $license = $this->licenseService->createLicense($request->validated());
        return $this->successResponse(
            new LicenseResource($license->load(['plan', 'organization'])),
            SuccessMessages::created('License'),
            HttpStatus::HTTP_CREATED,
        );
    }

    public function show(int $id): JsonResponse
    {
        $license = $this->licenseService->findById($id);
        $license->load(['plan', 'organization']);
        return $this->successResponse(new LicenseResource($license));
    }

    public function update(LicenseRequest $request, int $id): JsonResponse
    {
        $license = $this->licenseService->updateLicense($id, $request->validated());
        return $this->successResponse(
            new LicenseResource($license->load(['plan', 'organization'])),
            SuccessMessages::updated('License'),
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->licenseService->deleteLicense($id);
        return $this->successResponse(null, SuccessMessages::deleted('License'));
    }

    /**
     * Activate a license.
     */
    public function activate(int $id): JsonResponse
    {
        $license = $this->licenseService->activateLicense($id);
        return $this->successResponse(
            new LicenseResource($license->load(['plan', 'organization'])),
            SuccessMessages::updated('License activated'),
        );
    }

    /**
     * Suspend a license.
     */
    public function suspend(int $id): JsonResponse
    {
        $license = $this->licenseService->suspendLicense($id);
        return $this->successResponse(
            new LicenseResource($license->load(['plan', 'organization'])),
            SuccessMessages::updated('License suspended'),
        );
    }

    /**
     * Expire a license.
     */
    public function expire(int $id): JsonResponse
    {
        $license = $this->licenseService->expireLicense($id);
        return $this->successResponse(
            new LicenseResource($license->load(['plan', 'organization'])),
            SuccessMessages::updated('License expired'),
        );
    }
}
