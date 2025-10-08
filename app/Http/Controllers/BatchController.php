<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\BatchRequest;
use App\Http\Resources\BatchResource;
use App\Services\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends BaseController {
    public function __construct(
        private readonly BatchService $batchService,
    ) {}

    /**
     * Remove the specified batch.
     */
    public function destroy(string $id): JsonResponse {
        $batch = $this->batchService->findById($id);
        $this->batchService->deleteBatch($id);

        return ApiResponseMiddleware::deleteResponse('batch', $batch->toArray());
    }

    /**
     * Display a listing of batches for the authenticated user's organization.
     */
    public function index(Request $request): JsonResponse {
        $rawParams = $request->query();
        $processed = $this->batchService->processRequestParams($rawParams);
        $query     = $this->batchService->getFiltered($processed);
        $batches   = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            BatchResource::collection($batches),
            'batch',
            $batches->total(),
        );
    }

    /**
     * Display the specified batch.
     */
    public function show(Request $request, string $id): JsonResponse {
        $with  = $request->query('with', '') ? explode(',', $request->query('with')) : [];
        $batch = $this->batchService->findById($id, ['*'], $with);

        return ApiResponseMiddleware::showResponse(
            new BatchResource($batch),
            'batch',
            $batch->toArray(),
        );
    }

    /**
     * Store a newly created batch.
     */
    public function store(BatchRequest $request): JsonResponse {
        $batch = $this->batchService->createBatch($request->validated());

        return ApiResponseMiddleware::createResponse(
            new BatchResource($batch),
            'batch',
            $batch->toArray(),
        );
    }

    /**
     * Update the specified batch.
     */
    public function update(BatchRequest $request, string $id): JsonResponse {
        $updatedBatch = $this->batchService->updateBatch($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new BatchResource($updatedBatch),
            'batch',
            $updatedBatch->toArray(),
        );
    }
}
