<?php

namespace App\Http\Requests;

class ItemRequest extends BaseRequest
{
    /**
     * Get validation rules for inventory item creation and updates.
     */
    public function rules(): array
    {
        // For GET requests (like index with query parameters), no validation needed
        if ($this->isMethod('GET')) {
            return [];
        }

        $itemId = $this->route('item');

        return [
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:items,code,'.($itemId ? $itemId.',id,org_id,'.$this->org_id : 'NULL'),
            ],
            'barcode' => 'nullable|string',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:unit_of_measures,id',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'nullable|exists:users,id',
            'status_id' => 'nullable|exists:statuses,id',
            'specifications' => 'nullable|array',
            'is_active' => 'boolean',
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The item name is required',
            'code.required' => 'The item code is required',
            'code.unique' => 'This item code is already used in your organization.',
            'quantity.required' => 'The quantity is required',
            'quantity.numeric' => 'The quantity must be a number',
            'unit_id.required' => 'The unit of measure is required',
            'unit_id.exists' => 'The selected unit of measure is invalid.',
            'category_id.exists' => 'The selected category is invalid.',
            'user_id.exists' => 'The selected user is invalid.',
            'status_id.exists' => 'The selected status is invalid.',
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
        ];
    }
}
