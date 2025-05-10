<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService extends BaseService
{
    // create new service instance
    public function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }

    // get supplier with items
    public function getWithItems()
    {
        return $this->model->with('items')->get();
    }

    // get suppliers by name
    public function getByName(string $name)
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    // get active suppliers
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }
}
