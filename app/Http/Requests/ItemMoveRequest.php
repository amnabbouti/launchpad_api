<?php

namespace App\Http\Requests;

class ItemMoveRequest extends BaseRequest
{
    /**
     * Get validation rules
     */
    public function rules(): array
    {
        return [
            'to_location_id' => 'required|exists:locations,id',
            'from_location_id' => 'nullable|exists:locations,id|different:to_location_id',
            'quantity' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * error messages
     */
    public function messages(): array
    {
        return [
            'to_location_id.required' => 'The destination location is required.',
            'to_location_id.exists' => 'The selected destination location does not exist.',
            'from_location_id.exists' => 'The selected source location does not exist.',
            'from_location_id.different' => 'The source and destination locations must be different.',
            'quantity.numeric' => 'The quantity must be a number.',
            'quantity.min' => 'The quantity must be greater than 0.',
            'notes.string' => 'The notes must be text.',
            'notes.max' => 'The notes cannot exceed 1000 characters.',
        ];
    }
}
