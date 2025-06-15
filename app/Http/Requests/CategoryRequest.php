<?php

namespace App\Http\Requests;

class CategoryRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        $categoryId = $this->route('category')?->id ?? $this->category_id ?? null;

        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'path' => 'nullable|string',
            'org_id' => 'required|exists:organizations,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required',
            'name.string' => 'The category name must be a string',
            'name.max' => 'The category name cannot exceed 255 characters',
            'parent_id.exists' => 'The selected parent category does not exist',
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'is_active.boolean' => 'The active status must be true or false',
        ];
    }
}
