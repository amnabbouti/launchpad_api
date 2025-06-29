<?php

namespace App\Services;

use App\Models\ItemSupplier;
use App\Models\Supplier;
use App\Services\AuthorizationEngine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SupplierService extends BaseService
{
    protected ItemSupplier $itemSupplierModel;

    /**
     * Create a new service instance.
     */
    public function __construct(Supplier $supplier, ItemSupplier $itemSupplier)
    {
        parent::__construct($supplier);
        $this->itemSupplierModel = $itemSupplier;
    }

    /**
     * Create supplier with business logic validation.
     */
    public function create(array $data): Model
    {
        $data = $this->applySupplierBusinessRules($data);
        $this->validateSupplierBusinessRules($data);

        return parent::create($data);
    }

    /**
     * Update supplier with business logic validation.
     */
    public function update($id, array $data): Model
    {
        $data = $this->applySupplierBusinessRules($data, $id);
        $this->validateSupplierBusinessRules($data, $id);

        return parent::update($id, $data);
    }

    /**
     * Get filtered suppliers with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, fn ($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, fn ($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['email'] ?? null, fn ($q, $value) => $q->where('email', 'like', "%{$value}%"))
            ->when($filters['city'] ?? null, fn ($q, $value) => $q->where('city', 'like', "%{$value}%"))
            ->when($filters['country'] ?? null, fn ($q, $value) => $q->where('country', 'like', "%{$value}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get filtered item-supplier relationships.
     */
    public function getItemSuppliers(array $filters = []): Collection
    {
        $query = $this->itemSupplierModel->query();

        $query->when($filters['item_id'] ?? null, fn ($q, $id) => $q->where('item_id', $id))
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when(isset($filters['is_preferred']), fn ($q) => $q->where('is_preferred', $filters['is_preferred']))
            ->when($filters['min_price'] ?? null, fn ($q, $price) => $q->where('price', '>=', $price))
            ->when($filters['max_price'] ?? null, fn ($q, $price) => $q->where('price', '<=', $price))
            ->when($filters['currency'] ?? null, fn ($q, $currency) => $q->where('currency', $currency))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Find item-supplier relationship by ID.
     */
    public function findItemSupplierById(int $id, array $with = []): ?Model
    {
        $query = $this->itemSupplierModel->query();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    /**
     * Create item-supplier relationship with business logic validation.
     */
    public function createItemSupplier(array $data): Model
    {
        // Apply business rules and validation
        $data = $this->applyItemSupplierBusinessRules($data);
        $this->validateItemSupplierBusinessRules($data);

        return $this->itemSupplierModel->create($data);
    }

    /**
     * Update item-supplier relationship with business logic validation.
     */
    public function updateItemSupplier(int $id, array $data): Model
    {
        // Apply business rules and validation
        $data = $this->applyItemSupplierBusinessRules($data, $id);
        $this->validateItemSupplierBusinessRules($data, $id);

        $itemSupplier = $this->itemSupplierModel->findOrFail($id);
        $itemSupplier->update($data);

        return $itemSupplier->fresh();
    }

    /**
     * Delete item-supplier relationship.
     */
    public function deleteItemSupplier(int $id): bool
    {
        $itemSupplier = $this->itemSupplierModel->findOrFail($id);

        return $itemSupplier->delete();
    }

    /**
     * Set preferred supplier for an item.
     */
    public function setPreferredSupplier(int $itemId, int $supplierId): bool
    {
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
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'name' => $this->toString($params['name'] ?? null),
            'code' => $this->toString($params['code'] ?? null),
            'email' => $this->toString($params['email'] ?? null),
            'city' => $this->toString($params['city'] ?? null),
            'country' => $this->toString($params['country'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Process request parameters for item supplier relationships.
     */
    public function processItemSupplierParams(array $params): array
    {
        return [
            'item_id' => $this->toInt($params['item_id'] ?? null),
            'supplier_id' => $this->toInt($params['supplier_id'] ?? null),
            'is_preferred' => $this->toBool($params['is_preferred'] ?? null),
            'min_price' => isset($params['min_price']) && is_numeric($params['min_price']) ? (float) $params['min_price'] : null,
            'max_price' => isset($params['max_price']) && is_numeric($params['max_price']) ? (float) $params['max_price'] : null,
            'currency' => $this->toString($params['currency'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'name', 'code', 'email', 'city', 'country', 'is_active',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['items', 'itemSuppliers', 'stocks'];
    }

    /**
     * Determine operation type from request data.
     */
    public function determineOperationType(array $data): string
    {
        // Check for relationship type parameter
        $type = $data['type'] ?? null;
        
        if ($type === 'relationship' || isset($data['item_id'])) {
            return 'item_supplier_relationship';
        }
        
        return 'supplier';
    }

    /**
     * Apply business rules for supplier operations.
     * This handles the logic from SupplierRequest::isSupplierOperation().
     */
    private function applySupplierBusinessRules(array $data, $supplierId = null): array
    {
        return $data;
    }

    /**
     * Validate business rules for supplier operations.
     * This handles the uniqueness and conditional logic from SupplierRequest.
     */
    private function validateSupplierBusinessRules(array $data, $supplierId = null): void
    {
        $user = AuthorizationEngine::getCurrentUser();
        $orgId = $user->org_id;

        // Validate required fields for supplier operations
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('The supplier name is required');
        }

        // Validate code uniqueness within organization (if provided)
        if (isset($data['code']) && !empty($data['code'])) {
            $query = Supplier::where('code', $data['code'])
                ->where('org_id', $orgId);
            
            if ($supplierId) {
                $query->where('id', '!=', $supplierId);
            }
            
            if ($query->exists()) {
                throw new \InvalidArgumentException('This supplier code is already used in your organization');
            }
        }
    }

    /**
     * Apply business rules for item-supplier relationship operations.
     */
    private function applyItemSupplierBusinessRules(array $data, $relationshipId = null): array
    {
        return $data;
    }

    /**
     * Validate business rules for item-supplier relationship operations.
     */
    private function validateItemSupplierBusinessRules(array $data, $relationshipId = null): void
    {
        if (empty($data['item_id'])) {
            throw new \InvalidArgumentException('The item is required');
        }

        if (empty($data['supplier_id'])) {
            throw new \InvalidArgumentException('The supplier is required');
        }
    }
}
