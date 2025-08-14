<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\AppConstants;

class LocationRequest extends BaseRequest {
    /**
     * Error messages
     */
    public function messages(): array {
        return [
            'name.required'      => 'The location name is required and cannot be empty',
            'name.string'        => 'The location name must be a valid text string',
            'name.max'           => 'The location name cannot exceed 255 characters',
            'code.required'      => 'The location code is required and cannot be empty',
            'code.string'        => 'The location code must be a valid text string',
            'code.max'           => 'The location code cannot exceed 50 characters',
            'parent_id.integer'  => 'The parent location ID must be a valid number',
            'parent_id.exists'   => 'The selected parent location does not exist',
            'path.string'        => 'The location path must be a valid text string',
            'path.max'           => 'The location path cannot exceed 500 characters',
            'description.string' => 'The description must be a valid text string',
            'is_active.boolean'  => 'The active status must be true or false',
            'org_id.required'    => 'Organization ID is required',
            'org_id.exists'      => 'The specified organization does not exist',
        ];
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array {
        return [
            'name'        => 'required|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'code'        => 'required|string|max:50',
            'parent_id'   => 'nullable|exists:locations,id',
            'path'        => 'nullable|string|max:' . AppConstants::ADDRESS_MAX_LENGTH,
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'org_id'      => 'required|exists:organizations,id',
        ];
    }
}
