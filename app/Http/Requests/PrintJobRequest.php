<?php

declare(strict_types = 1);

namespace App\Http\Requests;

class PrintJobRequest extends BaseRequest {
    protected function getValidationRules(): array {
        if ($this->isMethod('POST')) {
            return [
                'entity_type'  => 'required|string|max:64',
                'entity_ids'   => 'required|array|min:1',
                'entity_ids.*' => 'string',
                'format'       => 'required|string|in:zpl,pdf,png,svg',
                'preset'       => 'nullable|string|max:64',
                'options'      => 'nullable|array',
                'printer_id'   => 'nullable|uuid|exists:printers,id',
                'copies'       => 'nullable|integer|min:1',
            ];
        }

        return [
            'entity_type'   => 'sometimes|string|max:64',
            'entity_ids'    => 'sometimes|array|min:1',
            'entity_ids.*'  => 'sometimes|string',
            'format'        => 'sometimes|string|in:zpl,pdf,png,svg',
            'preset'        => 'sometimes|nullable|string|max:64',
            'options'       => 'sometimes|array',
            'printer_id'    => 'sometimes|nullable|uuid|exists:printers,id',
            'copies'        => 'sometimes|integer|min:1',
            'status'        => 'sometimes|string|in:queued,processing,done,failed',
            'error_code'    => 'sometimes|nullable|string|max:64',
            'error_message' => 'sometimes|nullable|string',
            'artifact_path' => 'sometimes|nullable|string|max:255',
            'started_at'    => 'sometimes|date',
            'finished_at'   => 'sometimes|date',
        ];
    }
}
