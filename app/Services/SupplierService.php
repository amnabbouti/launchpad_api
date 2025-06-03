<?php

namespace App\Services;

use App\Models\ItemSupplier;
use App\Models\Supplier;
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
     * Get filtered suppliers with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // filters
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

        // Apply organization scope
        if (method_exists($this->itemSupplierModel, 'scopeForOrganization') && auth()->check()) {
            $query->forOrganization(auth()->user()->org_id);
        }

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
     * Create item-supplier relationship.
     */
    public function createItemSupplier(array $data): Model
    {
        return $this->itemSupplierModel->create($data);
    }

    /**
     * Update item-supplier relationship.
     */
    public function updateItemSupplier(int $id, array $data): Model
    {
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
     * Process request parameters for suppliers.
     */
    public function processSupplierParams(array $params): array
    {
        return [
            'name' => $params['name'] ?? null,
            'code' => $params['code'] ?? null,
            'email' => $params['email'] ?? null,
            'city' => $params['city'] ?? null,
            'country' => $params['country'] ?? null,
            'is_active' => isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            'with' => ! empty($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
        ];
    }

    /**
     * Process request parameters for item supplier relationships.
     */
    public function processItemSupplierParams(array $params): array
    {
        return [
            'item_id' => isset($params['item_id']) ? (int) $params['item_id'] : null,
            'supplier_id' => isset($params['supplier_id']) ? (int) $params['supplier_id'] : null,
            'is_preferred' => isset($params['is_preferred']) ? filter_var($params['is_preferred'], FILTER_VALIDATE_BOOLEAN) : null,
            'min_price' => isset($params['min_price']) ? (float) $params['min_price'] : null,
            'max_price' => isset($params['max_price']) ? (float) $params['max_price'] : null,
            'currency' => $params['currency'] ?? null,
            'with' => ! empty($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
        ];
    }
}
