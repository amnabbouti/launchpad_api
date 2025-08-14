<?php

declare(strict_types = 1);

namespace App\Http\Requests;

class CategoryRequest extends BaseRequest {
    /**
     * Error messages
     */
    public function messages(): array {
        return [
            'name.required'     => 'The category name is required',
            'name.string'       => 'The category name must be a string',
            'name.max'          => 'The category name cannot exceed 255 characters',
            'parent_id.exists'  => 'The selected parent category does not exist',
            'is_active.boolean' => 'The active status must be true or false',
        ];
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array {
        return [
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'path'      => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
