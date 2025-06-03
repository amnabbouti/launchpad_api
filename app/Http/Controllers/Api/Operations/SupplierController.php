<?php

namespace App\Http\Controllers\Api\Operations;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends BaseController
{
    public function __construct(
        private SupplierService $supplierService,
    ) {}

    /**
     * Display a listing of suppliers with dynamic filtering.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->get('type') === 'relationships') {
            $filters = $this->supplierService->processItemSupplierParams($request->all());
            $relationships = $this->supplierService->getItemSuppliers($filters);

            return $this->successResponse(
                SupplierResource::collection($relationships),
                SuccessMessages::RESOURCE_RETRIEVED,
            );
        }

        $filters = $this->supplierService->processSupplierParams($request->all());
        $suppliers = $this->supplierService->getFiltered($filters);

        return $this->successResponse(
            SupplierResource::collection($suppliers),
            SuccessMessages::RESOURCE_RETRIEVED,
        );
    }

    /**
     *  Create new item supplier relationship / supplier.
     */
    public function store(SupplierRequest $request): JsonResponse
    {
        if ($request->get('type') === 'relationship' || $request->has('item_id')) {
            try {
                $relationship = $this->supplierService->createItemSupplier($request->validated());

                return $this->successResponse(
                    new SupplierResource($relationship),
                    SuccessMessages::RESOURCE_CREATED,
                    HttpStatus::HTTP_CREATED,
                );
            } catch (\Exception $e) {
                return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_CONFLICT);
            }
        }

        $supplier = $this->supplierService->create($request->validated());

        return $this->successResponse(
            new SupplierResource($supplier),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Specified supplier or relationship.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            if ($request->get('type') === 'relationship') {
                $relationship = $this->supplierService->findItemSupplierById($id);

                if (! $relationship) {
                    return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
                }

                return $this->successResponse(
                    new SupplierResource($relationship),
                    SuccessMessages::RESOURCE_RETRIEVED,
                );
            }

            // return supplier
            $with = $request->get('with') ? explode(',', $request->get('with')) : [];
            $supplier = $this->supplierService->findById($id, ['*'], $with);

            if (! $supplier) {
                return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                new SupplierResource($supplier),
                SuccessMessages::RESOURCE_RETRIEVED,
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified supplier or relationship.
     */
    public function update(SupplierRequest $request, int $id): JsonResponse
    {
        try {
            if ($request->get('type') === 'relationship') {
                $relationship = $this->supplierService->updateItemSupplier($id, $request->validated());

                return $this->successResponse(
                    new SupplierResource($relationship),
                    SuccessMessages::RESOURCE_UPDATED,
                );
            }

            $supplier = $this->supplierService->update($id, $request->validated());

            return $this->successResponse(
                new SupplierResource($supplier),
                SuccessMessages::RESOURCE_UPDATED,
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete item supplier relationship / supplier.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            if ($request->get('type') === 'relationship') {
                $this->supplierService->deleteItemSupplier($id);
            } else {
                $this->supplierService->delete($id);
            }

            return $this->successResponse(
                null,
                SuccessMessages::RESOURCE_DELETED,
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_NOT_FOUND);
        }
    }
}
