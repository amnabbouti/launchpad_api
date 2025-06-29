<?php

namespace App\Http\Requests;

class StockRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'org_id' => 'required|exists:organizations,id',
            'item_id' => 'required|exists:items,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.01',
            'batch_number' => 'required|string|max:255',
            'received_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'unit_cost' => 'required|numeric|min:0',
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
            'item_id.required' => 'An item must be selected',
            'item_id.exists' => 'The selected item does not exist',
            'supplier_id.required' => 'A supplier must be selected',
            'supplier_id.exists' => 'The selected supplier does not exist',
            'quantity.required' => 'The quantity is required',
            'quantity.numeric' => 'The quantity must be a number',
            'quantity.min' => 'The quantity must be greater than 0',
            'batch_number.required' => 'The batch number is required for stock tracking',
            'received_date.required' => 'The received date is required',
            'received_date.date' => 'The received date must be a valid date',
            'expiry_date.date' => 'The expiry date must be a valid date',
            'unit_cost.required' => 'The unit cost is required',
            'unit_cost.numeric' => 'The unit cost must be a number',
            'unit_cost.min' => 'The unit cost cannot be negative',
            'is_active.boolean' => 'The active status must be true or false',
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
        ];
    }
}