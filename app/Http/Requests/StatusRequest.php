<?php

declare(strict_types = 1);

namespace App\Http\Requests;

class StatusRequest extends BaseRequest {
    /**
     * Error messages
     */
    public function messages(): array {
        return [
            'org_id.required'    => 'Organization ID is required',
            'org_id.exists'      => 'The selected organization does not exist',
            'name.required'      => 'The status name is required',
            'name.string'        => 'The status name must be a string',
            'name.max'           => 'The status name may not be greater than 255 characters',
            'description.string' => 'The description must be a string',
            'is_active.boolean'  => 'The active status must be true or false',
            'code.required'      => 'The status code is required',
            'code.string'        => 'The status code must be a string',
            'code.max'           => 'The status code may not be greater than 255 characters',
        ];
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array {
        return [
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'org_id'      => 'required|exists:organizations,id',
        ];
    }
}
