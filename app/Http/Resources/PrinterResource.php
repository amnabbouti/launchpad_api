<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PrinterResource extends BaseResource {
    public function toArray(Request $request): array {
        return [
            'id'           => $this->id,
            'org_id'       => $this->org_id,
            'name'         => $this->name,
            'driver'       => $this->driver,
            'host'         => $this->host,
            'port'         => $this->port,
            'config'       => $this->config,
            'capabilities' => $this->capabilities,
            'is_active'    => $this->is_active,
            'is_default'   => $this->is_default,
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
