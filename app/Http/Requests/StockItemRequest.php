<?php

namespace App\Http\Requests;

class StockItemRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        return [
            'org_id' => 'required|exists:organizations,id',
            'stock_id' => 'required|exists:stocks,id',
            'item_id' => 'required|exists:items,id',
            'serial_number' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'status_id' => 'required|exists:item_statuses,id',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'stock_id.required' => 'The stock is required',
            'stock_id.exists' => 'The selected stock is invalid',
            'item_id.required' => 'The item is required',
            'item_id.exists' => 'The selected item is invalid',
            'serial_number.string' => 'The serial number must be a valid string',
            'serial_number.max' => 'The serial number cannot exceed 255 characters',
            'barcode.string' => 'The barcode must be a valid string',
            'barcode.max' => 'The barcode cannot exceed 255 characters',
            'quantity.required' => 'The quantity is required',
            'quantity.min' => 'The quantity cannot be negative',
            'status_id.exists' => 'The selected status is invalid',
        ];
    }
}
