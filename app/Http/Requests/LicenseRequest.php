<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LicenseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'organization_id' => 'required|exists:organizations,id',
            'plan_id' => 'required|exists:plans,id',
            'seats' => 'required|integer|min:1',
            'license_key' => 'nullable|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'status' => 'nullable|string|in:active,inactive,expired,suspended',
            'meta' => 'nullable|array',
        ];
    }
}
