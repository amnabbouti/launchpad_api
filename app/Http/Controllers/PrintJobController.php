<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\PrintJobRequest;
use App\Http\Resources\PrintJobResource;
use App\Services\PrintJobService;
use Illuminate\Http\JsonResponse;

final class PrintJobController extends BaseController {
    public function __construct(
        private readonly PrintJobService $printJobService,
    ) {}

    public function destroy(string $id): JsonResponse {
        $this->printJobService->deleteJob($id);

        return ApiResponseMiddleware::deleteResponse('printjob');
    }

    public function index(PrintJobRequest $request): JsonResponse {
        $filters    = $this->printJobService->processRequestParams($request->query());
        $query      = $this->printJobService->getFiltered($filters);
        $totalCount = $query->count();
        $paginated  = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            PrintJobResource::collection($paginated),
            'printjob',
            $totalCount,
        );
    }

    public function show(string $id): JsonResponse {
        $job = $this->printJobService->findById($id, ['*'], ['printer', 'user', 'organization']);

        return ApiResponseMiddleware::showResponse(
            new PrintJobResource($job),
            'printjob',
            $job->toArray(),
        );
    }

    public function store(PrintJobRequest $request): JsonResponse {
        $job = $this->printJobService->createJob($request->validated());
        $job = $job->load(['printer', 'user', 'organization']);

        return ApiResponseMiddleware::createResponse(
            new PrintJobResource($job),
            'printjob',
            $job->toArray(),
        );
    }

    public function update(PrintJobRequest $request, string $id): JsonResponse {
        $job = $this->printJobService->updateJob($id, $request->validated());
        $job = $job->load(['printer', 'user', 'organization']);

        return ApiResponseMiddleware::updateResponse(
            new PrintJobResource($job),
            'printjob',
            $job->toArray(),
        );
    }
}
