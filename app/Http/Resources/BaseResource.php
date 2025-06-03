<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        return $this->addCommonData($data, $request);
    }

    protected function addCommonData(array $data, Request $request): array
    {
        if (property_exists($this->resource, 'created_at') && ! in_array('created_at', $this->resource->getHidden())) {
            $data['created_at'] = $this->created_at?->toISOString();
        }

        if (property_exists($this->resource, 'updated_at') && ! in_array('updated_at', $this->resource->getHidden())) {
            $data['updated_at'] = $this->updated_at?->toISOString();
        }

        return $data;
    }
}
