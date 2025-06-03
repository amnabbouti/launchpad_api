<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StockRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $stockId = $this->route('stock');
        $orgId = auth()->user()->org_id;

        return [
            'org_id' => 'required|exists:organizations,id',
            'batch_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stocks')->ignore($stockId)->where(fn ($query) => $query->where('org_id', $orgId)),
            ],
            'received_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:received_date',
            'supplier_id' => 'required|exists:suppliers,id',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'batch_number.required' => 'The batch number is required for stock tracking',
            'batch_number.unique' => 'This batch number already exists in your organization',
            'received_date.required' => 'The received date is required',
            'received_date.date' => 'The received date must be a valid date',
            'expiry_date.date' => 'The expiry date must be a valid date',
            'expiry_date.after' => 'The expiry date must be after the received date',
            'supplier_id.required' => 'A supplier must be selected',
            'supplier_id.exists' => 'The selected supplier does not exist',
            'unit_cost.required' => 'The unit cost is required',
            'unit_cost.numeric' => 'The unit cost must be a number',
            'unit_cost.min' => 'The unit cost cannot be negative',
            'is_active.boolean' => 'The active status must be true or false',
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
        ];
    }
}
