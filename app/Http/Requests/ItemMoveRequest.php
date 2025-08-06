<?php

namespace App\Http\Requests;

use App\Constants\AppConstants;

class ItemMoveRequest extends BaseRequest
{
    protected function getValidationRules(): array
    {
        return [
            'item_id' => 'required|exists:items,id',
            'to_location_id' => 'required|exists:locations,id',
            'from_location_id' => 'nullable|exists:locations,id|different:to_location_id',
            'quantity' => 'nullable|numeric|min:0.01|max:'.AppConstants::ITEM_MAX_QUANTITY,
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:'.AppConstants::REMARKS_MAX_LENGTH,
            'movement_type' => 'nullable|in:transfer,initial_placement,adjustment',
            'reference_id' => 'nullable|integer|min:1',
            'reference_type' => 'nullable|string|max:100|in:batch,order',
            'quantity_change' => 'nullable|numeric',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'item_id.required' => 'The item is required.',
            'item_id.exists' => 'The selected item does not exist.',
            'to_location_id.required' => 'The destination location is required.',
            'to_location_id.exists' => 'The selected destination location does not exist.',
            'from_location_id.exists' => 'The selected source location does not exist.',
            'from_location_id.different' => 'The source and destination locations must be different.',
            'quantity.numeric' => 'The quantity must be a number.',
            'quantity.min' => 'The quantity must be greater than 0.',
            'quantity_change.numeric' => 'The quantity change must be a number.',
            'reason.string' => 'The reason must be text.',
            'reason.max' => 'The reason cannot exceed 500 characters.',
            'notes.string' => 'The notes must be text.',
            'notes.max' => 'The notes cannot exceed 1000 characters.',
            'movement_type.in' => 'The movement type must be one of: transfer, initial_placement, adjustment.',
            'reference_id.integer' => 'The reference ID must be a number.',
            'reference_id.min' => 'The reference ID must be a positive number.',
            'reference_type.string' => 'The reference type must be text.',
            'reference_type.max' => 'The reference type cannot exceed 100 characters.',
            'reference_type.in' => 'The reference type must be one of: batch, order.',
        ];
    }

    /**
     * Get processed data ready for ItemMovementService
     */
    public function getMovementData(): array
    {
        $data = $this->validated();

        // Ensure movement_type is set for service processing
        if (! isset($data['movement_type'])) {
            $data['movement_type'] = ! isset($data['from_location_id'])
                ? 'initial_placement'
                : 'transfer';
        }

        // Clean up null values that shouldn't be passed to service
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
