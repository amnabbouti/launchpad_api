<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\PrinterRequest;
use App\Http\Resources\PrinterResource;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;

final class PrinterController extends BaseController {
    public function __construct(
        private readonly PrinterService $printerService,
    ) {}

    public function destroy(string $id): JsonResponse {
        $this->printerService->deletePrinter($id);

        return ApiResponseMiddleware::deleteResponse('printer');
    }

    public function index(PrinterRequest $request): JsonResponse {
        $filters    = $this->printerService->processRequestParams($request->query());
        $query      = $this->printerService->getFiltered($filters);
        $totalCount = $query->count();
        $paginated  = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            PrinterResource::collection($paginated),
            'printer',
            $totalCount,
        );
    }

    public function show(string $id): JsonResponse {
        $printer = $this->printerService->findById($id);

        return ApiResponseMiddleware::showResponse(
            new PrinterResource($printer->load(['organization'])),
            'printer',
            $printer->toArray(),
        );
    }

    public function store(PrinterRequest $request): JsonResponse {
        $printer = $this->printerService->createPrinter($request->validated());
        $printer = $printer->load(['organization']);

        return ApiResponseMiddleware::createResponse(
            new PrinterResource($printer),
            'printer',
            $printer->toArray(),
        );
    }

    public function update(PrinterRequest $request, string $id): JsonResponse {
        $printer = $this->printerService->updatePrinter($id, $request->validated());
        $printer = $printer->load(['organization']);

        return ApiResponseMiddleware::updateResponse(
            new PrinterResource($printer),
            'printer',
            $printer->toArray(),
        );
    }
}
