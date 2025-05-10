<?php

namespace App\Services;

use App\Models\Category;

class CategoryService extends BaseService
{
    // Create a new service instance.
    public function __construct(Category $category)
    {
        parent::__construct($category);
    }

    // Get categories with items
    public function getWithItems()
    {
        return $this->model->with('items')->get();
    }


    // Get categories by name
    public function getByName(string $name)
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }


    // Get active categories
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }
}
