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
     * Process request parameters with explicit validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'item_id' => $this->toInt($params['item_id'] ?? null),
            'supplier_id' => $this->toInt($params['supplier_id'] ?? null),
            'is_preferred' => $this->toBool($params['is_preferred'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
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
            // unset existing preferred suppliers
            $this->getQuery()
                ->where('item_id', $itemId)
                ->where('is_preferred', true)
                ->update(['is_preferred' => false]);

            // new preferred supplier
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

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'item_id', 'supplier_id', 'is_preferred',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['item', 'supplier'];
    }
}
