<?php

namespace App\Services;

use App\Models\UnitOfMeasure;

class UnitOfMeasureService extends BaseService
{
    // create a new instance
    public function __construct(UnitOfMeasure $unitOfMeasure)
    {
        parent::__construct($unitOfMeasure);
    }

    // get units of measure by name
    public function getByName(string $name)
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    // get units of measure
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }
}
