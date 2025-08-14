<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\ValidationMessages;

class MaintenanceConditionRequest extends BaseRequest {
    /**
     * error messages.
     */
    public function messages(): array {
        return [
            'item_id.required'                        => __(ValidationMessages::MAINTENANCE_CONDITION_ITEM_REQUIRED),
            'item_id.exists'                          => __(ValidationMessages::ITEM_NOT_EXISTS),
            'maintenance_category_id.required'        => __(ValidationMessages::MAINTENANCE_CONDITION_CATEGORY_REQUIRED),
            'maintenance_category_id.exists'          => __(ValidationMessages::MAINTENANCE_CATEGORY_NOT_FOUND),
            'unit_of_measure_id.required'             => __(ValidationMessages::MAINTENANCE_CONDITION_UNIT_REQUIRED),
            'unit_of_measure_id.exists'               => __(ValidationMessages::UNIT_NOT_EXISTS),
            'org_id.required'                         => __(ValidationMessages::ORG_REQUIRED),
            'org_id.exists'                           => __(ValidationMessages::INVALID_ORG),
            'mail_on_warning.boolean'                 => __(ValidationMessages::BOOLEAN_INVALID),
            'mail_on_maintenance.boolean'             => __(ValidationMessages::BOOLEAN_INVALID),
            'is_active.boolean'                       => __(ValidationMessages::BOOLEAN_INVALID),
            'maintenance_recurrence_quantity.integer' => __(ValidationMessages::INTEGER_INVALID),
            'maintenance_recurrence_quantity.min'     => __(ValidationMessages::MAINTENANCE_CONDITION_NEGATIVE_VALUES),
            'quantity_for_warning.numeric'            => __(ValidationMessages::NUMERIC_INVALID),
            'quantity_for_warning.min'                => __(ValidationMessages::MAINTENANCE_CONDITION_NEGATIVE_VALUES),
            'quantity_for_maintenance.numeric'        => __(ValidationMessages::NUMERIC_INVALID),
            'quantity_for_maintenance.min'            => __(ValidationMessages::MAINTENANCE_CONDITION_NEGATIVE_VALUES),
            'price_per_unit.numeric'                  => __(ValidationMessages::NUMERIC_INVALID),
            'price_per_unit.min'                      => __(ValidationMessages::MAINTENANCE_CONDITION_NEGATIVE_VALUES),

            'maintenance_warning_date.date' => __(ValidationMessages::INVALID_DATE),
            'maintenance_date.date'         => __(ValidationMessages::INVALID_DATE),

            'recurrence_unit.string' => __(ValidationMessages::STRING_INVALID),
            'recurrence_unit.max'    => __(ValidationMessages::STRING_TOO_LONG),

            'status_when_returned_id.exists' => __(ValidationMessages::STATUS_NOT_EXISTS),
            'status_when_exceeded_id.exists' => __(ValidationMessages::STATUS_NOT_EXISTS),
        ];
    }

    /**
     * validation rules.
     */
    protected function getValidationRules(): array {
        return [
            'org_id'                          => 'required|exists:organizations,id',
            'item_id'                         => 'required|exists:items,id',
            'maintenance_category_id'         => 'required|exists:maintenance_categories,id',
            'unit_of_measure_id'              => 'required|exists:unit_of_measures,id',
            'mail_on_warning'                 => 'boolean',
            'mail_on_maintenance'             => 'boolean',
            'maintenance_recurrence_quantity' => 'nullable|integer|min:0',
            'maintenance_warning_date'        => 'nullable|date',
            'maintenance_date'                => 'nullable|date',
            'quantity_for_warning'            => 'nullable|numeric|min:0',
            'quantity_for_maintenance'        => 'nullable|numeric|min:0',
            'recurrence_unit'                 => 'nullable|string|max:50',
            'price_per_unit'                  => 'nullable|numeric|min:0',
            'is_active'                       => 'boolean',
            'status_when_returned_id'         => 'nullable|exists:item_statuses,id',
            'status_when_exceeded_id'         => 'nullable|exists:item_statuses,id',
        ];
    }
}
