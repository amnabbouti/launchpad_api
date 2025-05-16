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

    // All
    public function index(): JsonResponse
    {
        $suppliers = $this->supplierService->all();
        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    // Create
    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());
        return $this->successResponse(new SupplierResource($supplier), 'Supplier created successfully', 201);
    }

    // Show
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);
        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }
        return $this->successResponse(new SupplierResource($supplier));
    }

    // Update
    public function update(SupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);
        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }
        $updatedSupplier = $this->supplierService->update($id, $request->validated());
        return $this->successResponse(new SupplierResource($updatedSupplier), 'Supplier updated successfully');
    }

    // Delete
    public function destroy(int $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);
        if (! $supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }
        $this->supplierService->delete($id);
        return $this->successResponse(null, 'Supplier deleted successfully');
    }

    // With items
    public function getWithItems(): JsonResponse
    {
        $suppliers = $this->supplierService->getWithItems();
        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    // By name
    public function getByName(string $name): JsonResponse
    {
        $suppliers = $this->supplierService->getByName($name);
        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    // Active
    public function getActive(): JsonResponse
    {
        $suppliers = $this->supplierService->getActive();
        return $this->successResponse(SupplierResource::collection($suppliers));
    }
}
