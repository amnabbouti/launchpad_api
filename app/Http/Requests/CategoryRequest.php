<?php

namespace App\Http\Requests;

class CategoryRequest extends BaseRequest
{
    // validation rules
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ];
    }

    // Custom messages for validation errors
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required',
            'parent_id.exists' => 'The selected parent category does not exist',
        ];
    }
}
