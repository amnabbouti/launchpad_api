<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\Hierarchy;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\LicenseRequest;
use App\Http\Resources\LicenseResource;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly LicenseService $licenseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $this->licenseService->processRequestParams($request->query());
        $licenses = $this->licenseService->getFiltered($filters);
        $totalCount = $licenses->count();

        return ApiResponseMiddleware::listResponse(
            LicenseResource::collection($licenses),
            'license',
            $totalCount
        );
    }

    public function store(LicenseRequest $request): JsonResponse
    {
        $license = $this->licenseService->createLicense($request->validated());
        $licenseWithRelations = $license->load(['plan', 'organization']);

        return ApiResponseMiddleware::createResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray()
        );
    }

    public function show(int $id): JsonResponse
    {
        $license = $this->licenseService->findById($id);
        $licenseWithRelations = $license->load(['plan', 'organization']);

        return ApiResponseMiddleware::showResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray()
        );
    }

    public function update(LicenseRequest $request, int $id): JsonResponse
    {
        $license = $this->licenseService->updateLicense($id, $request->validated());
        $licenseWithRelations = $license->load(['plan', 'organization']);

        return ApiResponseMiddleware::updateResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray()
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->licenseService->deleteLicense($id);

        return ApiResponseMiddleware::deleteResponse('license');
    }

    /**
     * Activate a license.
     */
    public function activate(int $id): JsonResponse
    {
        $license = $this->licenseService->activateLicense($id);
        $licenseWithRelations = $license->load(['plan', 'organization']);

        return ApiResponseMiddleware::updateResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray()
        );
    }

    /**
     * Suspend a license.
     */
    public function suspend(int $id): JsonResponse
    {
        $license = $this->licenseService->suspendLicense($id);
        $licenseWithRelations = $license->load(['plan', 'organization']);

        return ApiResponseMiddleware::updateResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray()
        );
    }

    /**
     * Expire a license.
     */
    public function expire(int $id): JsonResponse
    {
        $license = $this->licenseService->expireLicense($id);
        $licenseWithRelations = $license->load(['plan', 'organization']);

        return ApiResponseMiddleware::updateResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray()
        );
    }
}
