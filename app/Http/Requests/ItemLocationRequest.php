<?php

namespace App\Http\Requests;

class ItemLocationRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'org_id' => 'required|exists:organizations,id',
            'item_id' => 'required|exists:items,id',
            'location_id' => 'nullable|exists:locations,id',
            'from_location_id' => 'nullable|exists:locations,id',
            'to_location_id' => 'nullable|exists:locations,id',
            'quantity' => 'required|numeric|min:0',
            'moved_date' => 'nullable|date',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'item_id.required' => 'The item is required',
            'item_id.exists' => 'The selected item is invalid',
            'location_id.required' => 'The location is required',
            'location_id.exists' => 'The selected location is invalid',
            'from_location_id.required' => 'The source location is required',
            'from_location_id.exists' => 'The selected source location is invalid',
            'to_location_id.required' => 'The destination location is required',
            'to_location_id.exists' => 'The selected destination location is invalid',
            'quantity.required' => 'The quantity is required',
            'quantity.numeric' => 'The quantity must be a number',
            'quantity.min' => 'The quantity cannot be negative',
            'moved_date.date' => 'The moved date must be a valid date',
            'notes.max' => 'The notes field is too long',
        ];
    }
}
