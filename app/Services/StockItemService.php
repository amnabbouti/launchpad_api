<?php

namespace App\Services;

use App\Models\StockItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StockItemService extends BaseService
{
    public function __construct(StockItem $stockItem)
    {
        parent::__construct($stockItem);
    }

    /**
     * Get filtered stock items.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters using Laravel's when() method for clean conditional filtering
        $query->when($filters['stock_id'] ?? null, fn ($q, $value) => $q->where('stock_id', $value))
            ->when($filters['item_id'] ?? null, fn ($q, $value) => $q->where('item_id', $value))
            ->when($filters['status_id'] ?? null, fn ($q, $value) => $q->where('status_id', $value))
            ->when($filters['positive_quantity'] ?? null, fn ($q) => $q->where('quantity', '>', 0))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Create a new stock item with validated data.
     *
     * @param  array  $data  Validated stock item data
     * @return Model The created stock item
     */
    public function createStockItem(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a stock item with validated data.
     *
     * @param  int  $id  Stock item ID
     * @param  array  $data  Validated stock item data
     * @return Model The updated stock item
     */
    public function updateStockItem(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }
}
