<?php

namespace App\Http\Requests;

class MaintenanceCategoryRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:65535',
            'is_active' => 'nullable|boolean',
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The maintenance category name is required',
            'name.string' => 'The maintenance category name must be a string',
            'name.max' => 'The maintenance category name cannot exceed 255 characters',
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'remarks.string' => 'The remarks must be a string',
            'remarks.max' => 'The remarks field is too long',
            'is_active.boolean' => 'The active status must be true or false',
        ];
    }
}
