<?php

declare(strict_types = 1);

namespace App\Http\Requests;

class PrinterRequest extends BaseRequest {
    protected function getValidationRules(): array {
        if ($this->isMethod('POST')) {
            return [
                'name'         => 'required|string|max:255',
                'driver'       => 'required|string|in:zpl,ipp,qztray',
                'host'         => 'nullable|string|max:255',
                'port'         => 'nullable|integer|min:1|max:65535',
                'config'       => 'nullable|array',
                'capabilities' => 'nullable|array',
                'is_active'    => 'nullable|boolean',
                'is_default'   => 'nullable|boolean',
            ];
        }

        return [
            'name'         => 'sometimes|string|max:255',
            'driver'       => 'sometimes|string|in:zpl,ipp,qztray',
            'host'         => 'sometimes|nullable|string|max:255',
            'port'         => 'sometimes|nullable|integer|min:1|max:65535',
            'config'       => 'sometimes|array',
            'capabilities' => 'sometimes|array',
            'is_active'    => 'sometimes|boolean',
            'is_default'   => 'sometimes|boolean',
        ];
    }
}
