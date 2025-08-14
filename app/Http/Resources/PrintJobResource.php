<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PrintJobResource extends BaseResource {
    public function toArray(Request $request): array {
        return [
            'id'            => $this->id,
            'org_id'        => $this->org_id,
            'user_id'       => $this->user_id,
            'entity_type'   => $this->entity_type,
            'entity_ids'    => $this->entity_ids,
            'format'        => $this->format,
            'preset'        => $this->preset,
            'options'       => $this->options,
            'printer_id'    => $this->printer_id,
            'copies'        => (int) $this->copies,
            'status'        => $this->status,
            'error_code'    => $this->error_code,
            'error_message' => $this->error_message,
            'artifact_path' => $this->artifact_path,
            'started_at'    => $this->started_at?->toISOString(),
            'finished_at'   => $this->finished_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
