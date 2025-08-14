<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\AppConstants;

class ItemRequest extends BaseRequest {
    /**
     * Error messages
     */
    public function messages(): array {
        return [
            'name.required'           => 'The item name is required',
            'code.required'           => 'The item code is required',
            'tracking_mode.required'  => 'The tracking mode is required',
            'tracking_mode.in'        => 'The tracking mode must be: abstract, standard, or serialized',
            'unit_id.required'        => 'The unit of measure is required',
            'unit_id.exists'          => 'The selected unit of measure is invalid.',
            'category_id.exists'      => 'The selected category is invalid.',
            'user_id.exists'          => 'The selected user is invalid.',
            'status_id.exists'        => 'The selected status is invalid.',
            'org_id.required'         => 'The organization ID is required',
            'org_id.exists'           => 'The selected organization is invalid',
            'price.numeric'           => 'The price must be a number',
            'price.min'               => 'The price must be at least 0',
            'price.max'               => 'The price cannot exceed 999,999.99',
            'barcode.max'             => 'The barcode cannot exceed 255 characters',
            'description.max'         => 'The description cannot exceed 1000 characters',
            'notes.max'               => 'The notes cannot exceed 1000 characters',
            'parent_item_id.exists'   => 'The selected parent item is invalid.',
            'item_relation_id.exists' => 'The selected related item is invalid.',
            'serial_number.max'       => 'The serial number cannot exceed 255 characters',
        ];
    }

    /**
     * Get validation rules
     */
    protected function getValidationRules(): array {
        $itemId   = $this->route('item');
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'name'                 => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'code'                 => $isUpdate ? 'sometimes|string|max:50' : 'required|string|max:50',
            'barcode'              => 'nullable|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'description'          => 'nullable|string|max:' . AppConstants::DESCRIPTION_MAX_LENGTH,
            'tracking_mode'        => $isUpdate ? 'sometimes|in:abstract,standard,serialized' : 'required|in:abstract,standard,serialized',
            'unit_id'              => $isUpdate ? 'sometimes|string|exists:unit_of_measures,id' : 'required|string|exists:unit_of_measures,id',
            'price'                => 'nullable|numeric|min:0|max:' . AppConstants::ITEM_MAX_PRICE,
            'category_id'          => 'nullable|string|exists:categories,id',
            'user_id'              => 'nullable|string|exists:users,id',
            'specifications'       => 'nullable|array',
            'is_active'            => 'boolean',
            'org_id'               => $isUpdate ? 'sometimes|string|exists:organizations,id' : 'required|string|exists:organizations,id',
            'parent_item_id'       => 'nullable|string|exists:items,id',
            'item_relation_id'     => 'nullable|string|exists:items,id',
            'batch_id'             => 'nullable|string|exists:batches,id',
            'serial_number'        => 'nullable|string|max:255',
            'status_id'            => 'nullable|string|exists:statuses,id',
            'notes'                => 'nullable|string|max:' . AppConstants::REMARKS_MAX_LENGTH,
            'locations'            => 'nullable|array',
            'locations.*.id'       => 'required_with:locations|string',
            'locations.*.quantity' => 'required_with:locations|numeric|min:0|max:' . AppConstants::ITEM_MAX_QUANTITY,
        ];
    }
}
