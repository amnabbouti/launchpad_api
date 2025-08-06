<?php

namespace App\Http\Requests;

use App\Constants\AppConstants;

class BatchRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'org_id' => 'required|exists:organizations,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'batch_number' => 'required|string|max:'.AppConstants::NAME_MAX_LENGTH,
            'received_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'The selected supplier does not exist',
            'batch_number.required' => 'The batch number is required for batch tracking',
            'batch_number.string' => 'The batch number must be a string',
            'batch_number.max' => 'The batch number must not exceed 255 characters',
            'received_date.date' => 'The received date must be a valid date',
            'expiry_date.date' => 'The expiry date must be a valid date',
            'unit_cost.numeric' => 'The unit cost must be a number',
            'unit_cost.min' => 'The unit cost cannot be negative',
            'is_active.boolean' => 'The active status must be true or false',
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
        ];
    }
}
