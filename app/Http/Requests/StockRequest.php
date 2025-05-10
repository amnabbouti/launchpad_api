<?php

namespace App\Http\Requests;

class StockRequest extends BaseRequest
{
    // validation rules
    public function rules(): array
    {
        return [
            'serial_number' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'warranty_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'location_id' => 'nullable|exists:locations,id',
            'status_id' => 'nullable|exists:statuses,id',
        ];
    }

    // Custom messages for validation errors
    public function messages(): array
    {
        return [
            'purchase_price.min' => 'The purchase price cannot be negative',
            'location_id.exists' => 'The selected location does not exist',
            'status_id.exists' => 'The selected status does not exist',
        ];
    }
}
