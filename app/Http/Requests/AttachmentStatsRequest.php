<?php

namespace App\Http\Requests;

class AttachmentStatsRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        // No additional validation needed for GET request to fetch stats
        // The attachment ID is validated in the route parameter
        return [];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [];
    }
}
