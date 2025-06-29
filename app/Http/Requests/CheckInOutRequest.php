<?php

namespace App\Http\Requests;

use App\Constants\ErrorMessages;

class CheckInOutRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'trackable_id' => 'nullable|string',
            'trackable_type' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'checkin_user_id' => 'nullable|integer|exists:users,id',
            'checkout_location_id' => 'nullable|integer|exists:locations,id',
            'checkin_location_id' => 'nullable|integer|exists:locations,id',
            'checkout_date' => 'nullable|date',
            'checkin_date' => 'nullable|date',
            'quantity' => 'nullable|numeric|min:0.01',
            'checkin_quantity' => 'nullable|numeric|min:0.01',
            'expected_return_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'status_out_id' => 'nullable|integer|exists:statuses,id',
            'status_in_id' => 'nullable|integer|exists:statuses,id',
            'is_active' => 'nullable|boolean',
            'org_id' => 'nullable|integer|exists:organizations,id',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'quantity.numeric' => ErrorMessages::QUANTITY_NUMERIC,
            'quantity.min' => ErrorMessages::QUANTITY_MIN,
            'checkin_quantity.numeric' => ErrorMessages::QUANTITY_NUMERIC,
            'checkin_quantity.min' => ErrorMessages::QUANTITY_MIN,
            'status_out_id.exists' => 'Selected checkout status does not exist',
            'status_in_id.exists' => 'Selected checkin status does not exist',
            'checkin_location_id.exists' => 'Selected checkin location does not exist',
            'user_id.exists' => 'The selected user does not exist',
            'checkin_user_id.exists' => 'The selected checkin user does not exist',
            'checkout_location_id.exists' => 'The selected checkout location does not exist',
            'org_id.exists' => 'The selected organization does not exist',
        ];
    }
}
