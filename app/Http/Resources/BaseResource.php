<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use function in_array;

abstract class BaseResource extends JsonResource {
    public function toArray(Request $request): array {
        $data = parent::toArray($request);

        return $this->addCommonData($data, $request);
    }

    protected function addCommonData(array $data, Request $request): array {
        if (property_exists($this->resource, 'created_at') && ! in_array('created_at', $this->resource->getHidden(), true)) {
            $data['created_at'] = $this->created_at?->toISOString();
        }

        if (property_exists($this->resource, 'updated_at') && ! in_array('updated_at', $this->resource->getHidden(), true)) {
            $data['updated_at'] = $this->updated_at?->toISOString();
        }

        return $data;
    }
}
