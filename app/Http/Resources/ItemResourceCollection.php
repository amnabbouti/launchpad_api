<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResourceCollection extends BaseResourceCollection
{
    public $collects = ItemResource::class;

    public function toArray($request): array
    {

        return parent::toArray($request);
    }
}
