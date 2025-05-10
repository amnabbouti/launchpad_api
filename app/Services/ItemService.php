<?php

namespace App\Services;

use App\Models\Item;

class ItemService extends BaseService
{

    // create a new service instance
    public function __construct(Item $item)
    {
        parent::__construct($item);
    }

    // get item by category
    public function getByCategory(int $categoryId)
    {
        return $this->model->where('category_id', $categoryId)->get();
    }

    // get item by stock
    public function getByStock(int $stockId)
    {
        return $this->model->where('stock_id', $stockId)->get();
    }

    // get active items
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    // get all locations of an item
    public function getItemLocations(int $itemId)
    {
        $item = $this->model->findOrFail($itemId);
        return $item->locations()->get();
    }
}
