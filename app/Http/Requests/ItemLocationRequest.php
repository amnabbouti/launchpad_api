<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\AppConstants;

class ItemLocationRequest extends BaseRequest {
    /**
     * Error messages for ItemLocation validation
     */
    public function messages(): array {
        return [
            'org_id.required'      => 'Organization ID is required',
            'org_id.exists'        => 'The selected organization is invalid',
            'item_id.required'     => 'The item is required',
            'item_id.exists'       => 'The selected item is invalid',
            'location_id.required' => 'The location is required',
            'location_id.exists'   => 'The selected location is invalid',
            'quantity.required'    => 'The quantity is required',
            'quantity.numeric'     => 'The quantity must be a number',
            'quantity.min'         => 'The quantity cannot be negative',
            'moved_date.date'      => 'The moved date must be a valid date',
            'notes.max'            => 'The notes field is too long',
        ];
    }

    /**
     * Validation rules for ItemLocation operations (inventory management only)
     */
    protected function getValidationRules(): array {
        return [
            'org_id'      => 'nullable|exists:organizations,id',
            'item_id'     => 'required|exists:items,id',
            'location_id' => 'required|exists:locations,id',
            'quantity'    => 'required|numeric|min:0|max:' . AppConstants::ITEM_MAX_QUANTITY,
            'moved_date'  => 'nullable|date',
            'notes'       => 'nullable|string|max:' . AppConstants::REMARKS_MAX_LENGTH,
        ];
    }
}
