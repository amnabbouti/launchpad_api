<?php

declare(strict_types = 1);

namespace App\Http\Requests;

class AttachmentStatsRequest extends BaseRequest {
    /**
     * Custom attributes for validator errors
     */
    public function attributes(): array {
        return [];
    }

    /**
     * Error messages
     */
    public function messages(): array {
        return [];
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array {
        // No additional validation needed for GET request to fetch stats
        // The attachment ID is validated in the route parameter
        return [];
    }
}
