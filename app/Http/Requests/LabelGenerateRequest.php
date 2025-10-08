<?php

declare(strict_types = 1);

namespace App\Http\Requests;

class LabelGenerateRequest extends BaseRequest {
    protected function getValidationRules(): array {
        return [
            'entity_type'        => 'required|string|in:locations,items',
            'entity_ids'         => 'required|array|min:1',
            'entity_ids.*'       => 'string',
            'format'             => 'required|string|in:zpl,pdf,png',
            'options'            => 'nullable|array',
            'options.label_size' => 'sometimes|string',
            'options.dpi'        => 'sometimes|integer|min:100|max:600',
            'options.hri'        => 'sometimes|boolean',
            'options.save'       => 'sometimes|boolean',
            'options.png'        => 'sometimes|array',
            'options.png.height' => 'sometimes|integer|min:20|max:400',
            'options.png.scale'  => 'sometimes|integer|min:1|max:6',
            'printer_id'         => 'nullable|uuid|exists:printers,id',
        ];
    }
}
