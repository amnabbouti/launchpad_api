<?php

namespace App\Http\Resources;

class ItemResourceCollection extends BaseResourceCollection
{
    public $collects = ItemResource::class;

    public function toArray($request): array
    {

        return parent::toArray($request);
    }
}
