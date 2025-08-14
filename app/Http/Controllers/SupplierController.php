<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends BaseController {
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    /**
     * Delete item supplier relationship / supplier.
     */
    public function destroy(Request $request, string $id): JsonResponse {
        if ($request->get('type') === 'relationship') {
            $relationship = $this->supplierService->findItemSupplierById((string) $id);
            $this->supplierService->deleteItemSupplier((string) $id);

            return ApiResponseMiddleware::deleteResponse('supplier_relationship', $relationship->toArray());
        }
        $supplier = $this->supplierService->findById($id);
        $this->supplierService->delete($id);

        return ApiResponseMiddleware::deleteResponse('supplier', $supplier->toArray());
    }

    /**
     * Display a listing of suppliers with dynamic filtering.
     */
    public function index(Request $request): JsonResponse {
        if ($request->get('type') === 'relationships') {
            $filters            = $this->supplierService->processItemSupplierParams($request->all());
            $relationshipsQuery = $this->supplierService->getItemSuppliers($filters);
            $totalCount         = $relationshipsQuery->count();

            $relationships = $this->paginated($relationshipsQuery, $request);

            return ApiResponseMiddleware::listResponse(
                SupplierResource::collection($relationships),
                'supplier_relationship',
                $totalCount,
            );
        }

        $filters        = $this->supplierService->processRequestParams($request->all());
        $suppliersQuery = $this->supplierService->getFiltered($filters);
        $totalCount     = $suppliersQuery->count();

        $suppliers = $this->paginated($suppliersQuery, $request);

        return ApiResponseMiddleware::listResponse(
            SupplierResource::collection($suppliers),
            'supplier',
            $totalCount,
        );
    }

    /**
     * Specified supplier or relationship.
     */
    public function show(Request $request, string $id): JsonResponse {
        if ($request->get('type') === 'relationship') {
            $relationship = $this->supplierService->findItemSupplierById((string) $id);

            return ApiResponseMiddleware::success(new SupplierResource($relationship));
        }

        // return supplier
        $with     = $request->get('with') ? explode(',', $request->get('with')) : [];
        $supplier = $this->supplierService->findById($id, ['*'], $with);

        return ApiResponseMiddleware::success(new SupplierResource($supplier));
    }

    /**
     *  Create new item supplier relationship / supplier.
     */
    public function store(SupplierRequest $request): JsonResponse {
        if ($request->get('type') === 'relationship' || $request->has('item_id')) {
            $relationship = $this->supplierService->createItemSupplier($request->validated());

            return ApiResponseMiddleware::createResponse(
                new SupplierResource($relationship),
                'supplier_relationship',
                $relationship->toArray(),
            );
        }

        $supplier = $this->supplierService->create($request->validated());

        return ApiResponseMiddleware::createResponse(
            new SupplierResource($supplier),
            'supplier',
            $supplier->toArray(),
        );
    }

    /**
     * Update the specified supplier or relationship.
     */
    public function update(SupplierRequest $request, string $id): JsonResponse {
        if ($request->get('type') === 'relationship') {
            $relationship = $this->supplierService->updateItemSupplier((string) $id, $request->validated());

            return ApiResponseMiddleware::updateResponse(
                new SupplierResource($relationship),
                'supplier_relationship',
                $relationship->toArray(),
            );
        }

        $supplier = $this->supplierService->update($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new SupplierResource($supplier),
            'supplier',
            $supplier->toArray(),
        );
    }
}
