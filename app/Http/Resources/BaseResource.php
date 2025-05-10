<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected function addCommonData(array $data, Request $request): array
    {
        // common data or metadata
        // For example timestamps to all resources
        if (property_exists($this->resource, 'created_at') && !in_array('created_at', $this->resource->getHidden())) {
            $data['created_at'] = $this->created_at;
        }

        if (property_exists($this->resource, 'updated_at') && !in_array('updated_at', $this->resource->getHidden())) {
            $data['updated_at'] = $this->updated_at;
        }

        return $data;
    }

    public function toArray($request): array
    {
        // should be implemented by child classes
        // default implementation that returns all attributes
        $data = parent::toArray($request);

        return $this->addCommonData($data, $request);
    }
}
