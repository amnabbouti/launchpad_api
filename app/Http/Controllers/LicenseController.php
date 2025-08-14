<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\LicenseRequest;
use App\Http\Resources\LicenseResource;
use App\Services\LicensePaymentService;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends BaseController {
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly LicensePaymentService $licensePaymentService,
    ) {}

    public function destroy(string $id): JsonResponse {
        $this->licenseService->deleteLicense($id);

        return ApiResponseMiddleware::deleteResponse('license');
    }

    public function index(Request $request): JsonResponse {
        $filters    = $this->licenseService->processRequestParams($request->all());
        $query      = $this->licenseService->getFiltered($filters);
        $totalCount = $query->count();
        $paginated  = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            LicenseResource::collection($paginated),
            'license',
            $totalCount,
        );
    }

    /**
     * Create and send a Stripe invoice for the license (admin only)
     */
    public function invoice(string $id): JsonResponse {
        $license = $this->licenseService->findById($id);
        $result  = $this->licensePaymentService->processInvoiceFlow($license);

        if ($result['code'] === 'already_active') {
            return ApiResponseMiddleware::success(new LicenseResource($license->load('organization')), 'license.already_active');
        }

        if ($result['code'] === 'activated') {
            /** @var \App\Models\License $updated */
            $updated = $result['payload'];

            return ApiResponseMiddleware::success(new LicenseResource($updated->load('organization')), 'license.activated');
        }

        if ($result['code'] === 'invoice_pending') {
            return ApiResponseMiddleware::success($result['payload'], 'license.invoice_pending');
        }

        // invoice_created
        return ApiResponseMiddleware::success($result['payload'], 'license.invoice_created');
    }

    public function show(string $id): JsonResponse {
        $license              = $this->licenseService->findById($id);
        $licenseWithRelations = $license->load(['organization']);

        return ApiResponseMiddleware::showResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray(),
        );
    }

    public function store(LicenseRequest $request): JsonResponse {
        $license              = $this->licenseService->createLicense($request->validated());
        $licenseWithRelations = $license->load(['organization']);

        return ApiResponseMiddleware::createResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray(),
        );
    }

    public function update(LicenseRequest $request, string $id): JsonResponse {
        $payload = $request->validated();

        if (isset($payload['status'])) {
            $status = $payload['status'];
            if ($status === 'active') {
                $license = $this->licenseService->activateLicense($id);
            } elseif ($status === 'suspended') {
                $license = $this->licenseService->suspendLicense($id);
            } elseif ($status === 'expired') {
                $license = $this->licenseService->expireLicense($id);
            } else {
                $license = $this->licenseService->updateLicense($id, $payload);
            }
        } else {
            $license = $this->licenseService->updateLicense($id, $payload);
        }
        $licenseWithRelations = $license->load(['organization']);

        return ApiResponseMiddleware::updateResponse(
            new LicenseResource($licenseWithRelations),
            'license',
            $licenseWithRelations->toArray(),
        );
    }
}
