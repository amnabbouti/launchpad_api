<?php

namespace App\Services;

use App\Models\Stock;

class StockService extends BaseService
{
    // create a new service instance
    public function __construct(Stock $stock)
    {
        parent::__construct($stock);
    }

    // get stocks with items
    public function getWithItems()
    {
        return $this->model->with('items')->get();
    }

    // get active stocks
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }
}
