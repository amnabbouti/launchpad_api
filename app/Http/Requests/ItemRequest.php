<?php

namespace App\Http\Requests;

class ItemRequest extends BaseRequest
{
    /**
     * Get validation rules - PURE VALIDATION ONLY
     * Business logic handled in ItemService
     */
    protected function getValidationRules(): array
    {
        $itemId = $this->route('item');
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            // Basic validation - no business logic
            'name' => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'code' => $isUpdate ? 'sometimes|string|max:50' : 'required|string|max:50',
            'barcode' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tracking_mode' => $isUpdate ? 'sometimes|in:abstract,bulk,serialized' : 'required|in:abstract,bulk,serialized',
            'unit_id' => $isUpdate ? 'sometimes|integer|exists:unit_of_measures,id' : 'required|integer|exists:unit_of_measures,id',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'category_id' => 'nullable|integer|exists:categories,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'specifications' => 'nullable|array',
            'is_active' => 'boolean',
            'org_id' => $isUpdate ? 'sometimes|integer|exists:organizations,id' : 'required|integer|exists:organizations,id',
            'parent_item_id' => 'nullable|integer|exists:items,id',
            'item_relation_id' => 'nullable|integer|exists:items,id',
            'serial_number' => 'nullable|string|max:255',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'notes' => 'nullable|string|max:1000',
            'locations' => 'nullable|array',
            'locations.*.id' => 'required_with:locations|string',
            'locations.*.quantity' => 'required_with:locations|numeric|min:0',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The item name is required',
            'code.required' => 'The item code is required',
            'tracking_mode.required' => 'The tracking mode is required',
            'tracking_mode.in' => 'The tracking mode must be: abstract, bulk, or serialized',
            'unit_id.required' => 'The unit of measure is required',
            'unit_id.exists' => 'The selected unit of measure is invalid.',
            'category_id.exists' => 'The selected category is invalid.',
            'user_id.exists' => 'The selected user is invalid.',
            'status_id.exists' => 'The selected status is invalid.',
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'price.numeric' => 'The price must be a number',
            'price.min' => 'The price must be at least 0',
            'price.max' => 'The price cannot exceed 999,999.99',
            'barcode.max' => 'The barcode cannot exceed 255 characters',
            'description.max' => 'The description cannot exceed 1000 characters',
            'notes.max' => 'The notes cannot exceed 1000 characters',
            'parent_item_id.exists' => 'The selected parent item is invalid.',
            'item_relation_id.exists' => 'The selected related item is invalid.',
            'serial_number.max' => 'The serial number cannot exceed 255 characters',
        ];
    }
}
