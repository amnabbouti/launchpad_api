<?php

namespace App\Http\Requests;

use App\Constants\ValidationMessages;

class MaintenanceCategoryRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
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
            'name.required' => __(ValidationMessages::MAINTENANCE_CATEGORY_NAME_REQUIRED),
            'name.string' => __(ValidationMessages::STRING_INVALID),
            'name.max' => __(ValidationMessages::STRING_TOO_LONG),
            'org_id.required' => __(ValidationMessages::ORG_REQUIRED),
            'org_id.exists' => __(ValidationMessages::INVALID_ORG),
            'remarks.string' => __(ValidationMessages::STRING_INVALID),
            'remarks.max' => __(ValidationMessages::STRING_TOO_LONG),
            'is_active.boolean' => __(ValidationMessages::BOOLEAN_INVALID),
        ];
    }
}
