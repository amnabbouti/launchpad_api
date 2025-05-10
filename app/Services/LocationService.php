<?php

namespace App\Services;

use App\Models\Location;

class LocationService extends BaseService
{
    // create a new service instance
    public function __construct(Location $location)
    {
        parent::__construct($location);
    }

    // get a location with items
    public function getWithItems()
    {
        return $this->model->with('items')->get();
    }

    // get active locations
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }
}
