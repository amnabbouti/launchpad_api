<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ItemService extends BaseService
{
    // Constructor
    public function __construct(Item $item)
    {
        parent::__construct($item);
    }

    // By category
    public function getByCategory(int $categoryId): Collection
    {
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Category ID must be a positive integer');
        }
        
        return $this->model->where('category_id', $categoryId)
            ->get();
    }

    // By stock
    public function getByStock(int $stockId): Collection
    {
        if ($stockId <= 0) {
            throw new InvalidArgumentException('Stock ID must be a positive integer');
        }
        
        return $this->model->where('stock_id', $stockId)
            ->get();
    }

    // Active items
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->get();
    }

    // Item locations
    public function getItemLocations(int $itemId): Collection
    {
        if ($itemId <= 0) {
            throw new InvalidArgumentException('Item ID must be a positive integer');
        }
        
        $item = $this->model->findOrFail($itemId);

        return $item->locations()
            ->get();
    }
}
