<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;

class SupplierController extends BaseController
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    // display a list of suppliers
    public function index(): JsonResponse
    {
        $suppliers = $this->supplierService->all();

        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    // store a new supplier
    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return $this->successResponse(new SupplierResource($supplier), 'Supplier created successfully', 201);
    }

    // get a specific supplier
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);

        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        return $this->successResponse(new SupplierResource($supplier));
    }

    // update a supplier
    public function update(SupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);

        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        $updatedSupplier = $this->supplierService->update($id, $request->validated());

        return $this->successResponse(new SupplierResource($updatedSupplier), 'Supplier updated successfully');
    }

    // remove a supplier
    public function destroy(int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);

        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        $this->supplierService->delete($id);

        return $this->successResponse(null, 'Supplier deleted successfully');
    }

    // get a supplier with items
    public function getWithItems(): JsonResponse
    {
        $suppliers = $this->supplierService->getWithItems();

        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    // get supplier by name
    public function getByName(string $name): JsonResponse
    {
        $suppliers = $this->supplierService->getByName($name);

        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    // get an active supplier
    public function getActive(): JsonResponse
    {
        $suppliers = $this->supplierService->getActive();

        return $this->successResponse(SupplierResource::collection($suppliers));
    }
}
