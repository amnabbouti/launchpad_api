<?php

namespace App\Http\Requests;

class LicenseRequest extends BaseRequest
{
    protected function getValidationRules(): array
    {
        // Create vs Update: require minimal fields on update to support status-only transitions
        if ($this->isMethod('POST')) {
            return [
                'org_id' => 'required|exists:organizations,id',
                'seats' => 'required|integer|min:1',
                'license_key' => 'nullable|string|max:255',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after:starts_at',
                'status' => 'nullable|string|in:active,inactive,expired,suspended',
                'price' => 'nullable|numeric|min:0',
                'name' => 'nullable|string|max:255',
                'features' => 'nullable|array',
                'meta' => 'nullable|array',
            ];
        }

        // PATCH/PUT: allow partial updates (status, seats, etc.)
        return [
            'org_id' => 'sometimes|integer|exists:organizations,id',
            'seats' => 'sometimes|integer|min:1',
            'license_key' => 'sometimes|string|max:255',
            'starts_at' => 'sometimes|date|nullable',
            'ends_at' => 'sometimes|date|after:starts_at|nullable',
            'status' => 'sometimes|string|in:active,inactive,expired,suspended',
            'price' => 'sometimes|numeric|min:0|nullable',
            'name' => 'sometimes|string|max:255|nullable',
            'features' => 'sometimes|array|nullable',
            'meta' => 'sometimes|array|nullable',
        ];
    }
}
