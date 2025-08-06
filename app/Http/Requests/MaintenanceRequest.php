<?php

namespace App\Http\Requests;

use App\Constants\AppConstants;
use App\Constants\ValidationMessages;

class MaintenanceRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        $rules = [
            'user_id' => 'nullable|exists:users,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'remarks' => 'nullable|string|max:'.AppConstants::REMARKS_MAX_LENGTH,
            'cost' => 'nullable|numeric|min:0',
            'date_expected_back_from_maintenance' => 'nullable|date',
            'date_back_from_maintenance' => 'nullable|date',
            'date_in_maintenance' => 'nullable|date',
            'is_repair' => 'nullable|boolean',
            'status_out_id' => 'nullable|exists:statuses,id',
            'status_in_id' => 'nullable|exists:statuses,id',
            'trigger_value' => 'nullable|numeric',
        ];

        // Only require item_id or condition_id for creation
        if (! $isUpdate) {
            $rules['item_id'] = 'required_without:condition_id|exists:items,id';
            $rules['condition_id'] = 'required_without:item_id|exists:maintenance_conditions,id';
        } else {
            $rules['item_id'] = 'nullable|exists:items,id';
            $rules['condition_id'] = 'nullable|exists:maintenance_conditions,id';
        }

        return $rules;
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'item_id.required_without' => __(ValidationMessages::MAINTENANCE_ITEM_REQUIRED),
            'item_id.exists' => __(ValidationMessages::ITEM_NOT_EXISTS),
            'condition_id.required_without' => __(ValidationMessages::MAINTENANCE_CONDITION_REQUIRED),
            'condition_id.exists' => __(ValidationMessages::MAINTENANCE_CONDITION_NOT_FOUND),
            'user_id.exists' => __(ValidationMessages::USER_NOT_EXISTS),
            'supplier_id.exists' => __(ValidationMessages::SUPPLIER_NOT_EXISTS),
            'status_out_id.exists' => __(ValidationMessages::STATUS_NOT_EXISTS),
            'status_in_id.exists' => __(ValidationMessages::STATUS_NOT_EXISTS),
            'cost.numeric' => __(ValidationMessages::MAINTENANCE_COST_INVALID),
            'cost.min' => __(ValidationMessages::MAINTENANCE_COST_NEGATIVE),
            'remarks.max' => __(ValidationMessages::MAINTENANCE_REMARKS_TOO_LONG),
            'trigger_value.numeric' => __(ValidationMessages::MAINTENANCE_TRIGGER_VALUE_INVALID),
        ];
    }
}
