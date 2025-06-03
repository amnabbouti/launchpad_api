<?php

namespace App\Services;

use App\Models\ItemSupplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ItemSupplierService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(ItemSupplier $itemSupplier)
    {
        parent::__construct($itemSupplier);
    }

    /**
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        return [
            'with' => isset($params['with'])
                ? (is_string($params['with']) ? array_filter(explode(',', $params['with'])) : $params['with'])
                : null,
            'item_id' => isset($params['item_id']) ? (int) $params['item_id'] : null,
            'supplier_id' => isset($params['supplier_id']) ? (int) $params['supplier_id'] : null,
            'is_preferred' => isset($params['is_preferred']) ? filter_var($params['is_preferred'], FILTER_VALIDATE_BOOLEAN) : null,
        ];
    }

    /**
     * Get filtered item suppliers.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return $this->getQuery()
            ->when($filters['item_id'] ?? null, fn ($q, $id) => $q->where('item_id', $id))
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when(isset($filters['is_preferred']), fn ($q) => $q->where('is_preferred', $filters['is_preferred']))
            ->when($filters['with'] ?? null, fn ($q, $with) => $q->with($with))
            ->get();
    }

    /**
     * Get item suppliers by item ID.
     */
    public function getByItem(int $itemId): Collection
    {
        return $this->getFiltered(['item_id' => $itemId]);
    }

    /**
     * Get item suppliers by supplier ID.
     */
    public function getBySupplier(int $supplierId): Collection
    {
        return $this->getFiltered(['supplier_id' => $supplierId]);
    }

    /**
     * Create a new item supplier with validated data.
     *
     * @param  array  $data  Validated item supplier data
     * @return Model The created item supplier
     */
    public function createItemSupplier(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update an item supplier with validated data.
     */
    public function updateItemSupplier(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Get preferred supplier for an item.
     */
    public function getPreferredSupplier(int $itemId): ?Model
    {
        return $this->getFiltered(['item_id' => $itemId, 'is_preferred' => true])->first();
    }

    /**
     * Set a supplier as preferred for an item.
     */
    public function setPreferredSupplier(int $itemId, int $supplierId): bool
    {
        try {
            // unset any existing preferred suppliers for this item
            $this->getQuery()
                ->where('item_id', $itemId)
                ->where('is_preferred', true)
                ->update(['is_preferred' => false]);

            // Then set the new preferred supplier
            $itemSupplier = $this->getQuery()
                ->where('item_id', $itemId)
                ->where('supplier_id', $supplierId)
                ->first();

            if (! $itemSupplier) {
                return false;
            }

            $this->update($itemSupplier->id, ['is_preferred' => true]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
