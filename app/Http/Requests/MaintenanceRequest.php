<?php

namespace App\Http\Requests;

class MaintenanceRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        // For GET requests (like index with query parameters), no validation needed
        if ($this->isMethod('GET')) {
            return [];
        }

        // For POST/PUT requests, apply full validation
        return [
            'remarks' => 'nullable|string',
            'invoice_nbr' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'date_expected_back_from_maintenance' => 'nullable|date',
            'date_back_from_maintenance' => 'nullable|date',
            'date_in_maintenance' => 'nullable|date',
            'is_repair' => 'boolean',
            'supplier_id' => 'required|exists:suppliers,id',
            'stock_item_id' => 'required|exists:stock_items,id',
            'user_id' => 'nullable|exists:users,id',
            'status_out_id' => 'nullable|exists:statuses,id',
            'status_in_id' => 'nullable|exists:statuses,id',
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'The selected supplier does not exist',
            'stock_item_id.exists' => 'The selected stock item does not exist',
            'user_id.exists' => 'The selected user does not exist',
            'status_out_id.exists' => 'The selected status does not exist',
            'status_in_id.exists' => 'The selected status does not exist',
        ];
    }
}
