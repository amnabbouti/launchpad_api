<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\ItemSupplier;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SupplierService extends BaseService {
    protected ItemSupplier $itemSupplierModel;

    /**
     * Create a new service instance.
     */
    public function __construct(Supplier $supplier, ItemSupplier $itemSupplier) {
        parent::__construct($supplier);
        $this->itemSupplierModel = $itemSupplier;
    }

    /**
     * Create a supplier with business logic validation.
     */
    public function create(array $data): Model {
        $data = $this->applySupplierBusinessRules($data);
        $this->validateSupplierBusinessRules($data);

        return parent::create($data);
    }

    /**
     * Create an item-supplier relationship with business logic validation.
     */
    public function createItemSupplier(array $data): Model {
        // Apply business rules and validation
        $data = $this->applyItemSupplierBusinessRules($data);
        $this->validateItemSupplierBusinessRules($data);

        return $this->itemSupplierModel->create($data);
    }

    /**
     * Delete item-supplier relationship.
     */
    public function deleteItemSupplier(string $id): bool {
        $itemSupplier = $this->itemSupplierModel->findOrFail($id);

        return $itemSupplier->delete();
    }

    /**
     * Determine an operation type from request data.
     */
    public function determineOperationType(array $data): string {
        // Check for relationship type parameter
        $type = $data['type'] ?? null;

        if ($type === 'relationship' || isset($data['item_id'])) {
            return 'item_supplier_relationship';
        }

        return 'supplier';
    }

    /**
     * Find an item-supplier relationship by ID.
     */
    public function findItemSupplierById(string $id, array $with = []): ?Model {
        $query = $this->itemSupplierModel->query();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    /**
     * Get filtered suppliers with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, static fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, static fn ($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['email'] ?? null, static fn ($q, $value) => $q->where('email', 'like', "%{$value}%"))
            ->when($filters['city'] ?? null, static fn ($q, $value) => $q->where('city', 'like', "%{$value}%"))
            ->when($filters['country'] ?? null, static fn ($q, $value) => $q->where('country', 'like', "%{$value}%"))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Get filtered item-supplier relationships.
     */
    public function getItemSuppliers(array $filters = []): Builder {
        $query = $this->itemSupplierModel->query();

        $query->when($filters['item_id'] ?? null, static fn ($q, $id) => $q->where('item_id', $id))
            ->when($filters['supplier_id'] ?? null, static fn ($q, $id) => $q->where('supplier_id', $id))
            ->when(isset($filters['is_preferred']), static fn ($q) => $q->where('is_preferred', $filters['is_preferred']))
            ->when($filters['min_price'] ?? null, static fn ($q, $price) => $q->where('price', '>=', $price))
            ->when($filters['max_price'] ?? null, static fn ($q, $price) => $q->where('price', '<=', $price))
            ->when($filters['currency'] ?? null, static fn ($q, $currency) => $q->where('currency', $currency))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Process request parameters for item supplier relationships.
     */
    public function processItemSupplierParams(array $params): array {
        return [
            'item_id'      => $this->toString($params['item_id'] ?? null),
            'supplier_id'  => $this->toString($params['supplier_id'] ?? null),
            'is_preferred' => $this->toBool($params['is_preferred'] ?? null),
            'min_price'    => isset($params['min_price']) && is_numeric($params['min_price']) ? (float) $params['min_price'] : null,
            'max_price'    => isset($params['max_price']) && is_numeric($params['max_price']) ? (float) $params['max_price'] : null,
            'currency'     => $this->toString($params['currency'] ?? null),
            'with'         => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array {
        // Validate parameters against the allowlist
        $this->validateParams($params);

        return [
            'name'      => $this->toString($params['name'] ?? null),
            'code'      => $this->toString($params['code'] ?? null),
            'email'     => $this->toString($params['email'] ?? null),
            'city'      => $this->toString($params['city'] ?? null),
            'country'   => $this->toString($params['country'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with'      => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Set a preferred supplier for an item.
     */
    public function setPreferredSupplier(string $itemId, string $supplierId): bool {
        // Unset all preferred
        $this->itemSupplierModel->where('item_id', $itemId)
            ->update(['is_preferred' => false]);

        // Set preferred
        return $this->itemSupplierModel
            ->where('item_id', $itemId)
            ->where('supplier_id', $supplierId)
            ->update(['is_preferred' => true]) > 0;
    }

    /**
     * Update supplier with business logic validation.
     */
    public function update($id, array $data): Model {
        $data = $this->applySupplierBusinessRules($data);
        $this->validateSupplierBusinessRules($data, $id);

        return parent::update($id, $data);
    }

    /**
     * Update the item-supplier relationship with business logic validation.
     */
    public function updateItemSupplier(string $id, array $data): Model {
        // Apply business rules and validation
        $data = $this->applyItemSupplierBusinessRules($data);
        $this->validateItemSupplierBusinessRules($data);

        $itemSupplier = $this->itemSupplierModel->findOrFail($id);
        $itemSupplier->update($data);

        return $itemSupplier->fresh();
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'name',
            'code',
            'email',
            'city',
            'country',
            'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array {
        return ['items', 'itemSuppliers', 'stocks'];
    }

    /**
     * Apply business rules for item-supplier relationship operations.
     */
    private function applyItemSupplierBusinessRules(array $data): array {
        return $data;
    }

    /**
     * Apply business rules for supplier operations.
     */
    private function applySupplierBusinessRules(array $data): array {
        return $data;
    }

    /**
     * Validate business rules for item-supplier relationship operations.
     */
    private function validateItemSupplierBusinessRules(array $data): void {
        if (empty($data['item_id'])) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_REQUIRED));
        }

        if (empty($data['supplier_id'])) {
            throw new InvalidArgumentException(__(ErrorMessages::SUPPLIER_REQUIRED));
        }
    }

    /**
     * Validate business rules for supplier operations.
     */
    private function validateSupplierBusinessRules(array $data, $supplierId = null): void {
        if (empty($data['name'])) {
            throw new InvalidArgumentException(__(ErrorMessages::SUPPLIER_NAME_REQUIRED));
        }

        // Validate code uniqueness if provided (RLS handles org scoping)
        if (! empty($data['code'])) {
            $query = Supplier::where('code', $data['code']);

            if ($supplierId) {
                $query->where('id', '!=', $supplierId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException(__(ErrorMessages::SUPPLIER_CODE_EXISTS));
            }
        }
    }
}
