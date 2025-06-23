<?php

namespace App\Http\Requests;

use App\Constants\ErrorMessages;

class CheckInOutRequest extends BaseRequest
{
    /**
     * Validation rules.
     */
    public function rules(): array
    {
        // For GET requests (like index with query parameters), no validation needed
        if ($this->isMethod('GET')) {
            return [];
        }

        $route = $this->route()->getName();

        // Checkout rules
        if ($route === 'checks.out') {
            return [
                'quantity' => 'required|numeric|min:0.01',
                'expected_return_date' => 'nullable|date|after:today',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'status_out_id' => 'nullable|integer|exists:statuses,id',
                'org_id' => 'required|exists:organizations,id',
            ];
        }

        // Checkin rules
        if ($route === 'checks.in') {
            return [
                'checkin_quantity' => 'required|numeric|min:0.01',
                'checkin_location_id' => 'nullable|integer|exists:locations,id',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'status_in_id' => 'nullable|integer|exists:statuses,id',
                'org_id' => 'required|exists:organizations,id',
            ];
        }

        // Default validation for POST/PUT requests
        return [
            'trackable_id' => 'nullable|string',
            'trackable_type' => 'nullable|string',
            'checkout_date' => 'nullable|date',
            'quantity' => 'nullable|numeric|min:0.01',
            'checkin_date' => 'nullable|date',
            'checkin_quantity' => 'nullable|numeric|min:0.01',
            'expected_return_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'quantity.required' => ErrorMessages::QUANTITY_REQUIRED,
            'quantity.numeric' => ErrorMessages::QUANTITY_NUMERIC,
            'quantity.min' => ErrorMessages::QUANTITY_MIN,
            'checkin_quantity.required' => ErrorMessages::QUANTITY_REQUIRED,
            'checkin_quantity.numeric' => ErrorMessages::QUANTITY_NUMERIC,
            'checkin_quantity.min' => ErrorMessages::QUANTITY_MIN,
            'expected_return_date.after' => 'Expected return date must be in the future',
            'status_out_id.exists' => 'Selected checkout status does not exist',
            'status_in_id.exists' => 'Selected checkin status does not exist',
            'checkin_location_id.exists' => 'Selected checkin location does not exist',
            'user_id.exists' => 'The selected user does not exist',
            'checkin_user_id.exists' => 'The selected checkin user does not exist',
            'checkout_location_id.exists' => 'The selected checkout location does not exist',
        ];
    }
}
