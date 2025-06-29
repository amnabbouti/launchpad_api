<?php

namespace App\Http\Requests;

class MaintenanceConditionRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    protected function getValidationRules(): array
    {
        return [
            'org_id' => 'required|exists:organizations,id',
            'item_id' => 'required|exists:items,id',
            'maintenance_category_id' => 'required|exists:maintenance_categories,id',
            'unit_of_measure_id' => 'required|exists:unit_of_measures,id',
            'mail_on_warning' => 'boolean',
            'mail_on_maintenance' => 'boolean',
            'maintenance_recurrence_quantity' => 'nullable|integer|min:0',
            'maintenance_warning_date' => 'nullable|date',
            'maintenance_date' => 'nullable|date',
            'quantity_for_warning' => 'nullable|numeric|min:0',
            'quantity_for_maintenance' => 'nullable|numeric|min:0',
            'recurrence_unit' => 'nullable|string|max:50',
            'price_per_unit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'status_when_returned_id' => 'nullable|exists:item_statuses,id',
            'status_when_exceeded_id' => 'nullable|exists:item_statuses,id',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'item_id.required' => 'The item is required',
            'item_id.exists' => 'The selected item is invalid',
            'maintenance_category_id.required' => 'The maintenance category is required',
            'maintenance_category_id.exists' => 'The selected maintenance category is invalid',
            'unit_of_measure_id.required' => 'The unit of measure is required',
            'unit_of_measure_id.exists' => 'The selected unit of measure is invalid',
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',

            'mail_on_warning.boolean' => 'Mail on warning must be true or false',
            'mail_on_maintenance.boolean' => 'Mail on maintenance must be true or false',
            'is_active.boolean' => 'Active status must be true or false',

            'maintenance_recurrence_quantity.integer' => 'Maintenance recurrence quantity must be a whole number',
            'maintenance_recurrence_quantity.min' => 'Maintenance recurrence quantity cannot be negative',
            'quantity_for_warning.numeric' => 'Warning quantity must be a valid number',
            'quantity_for_warning.min' => 'Warning quantity cannot be negative',
            'quantity_for_maintenance.numeric' => 'Maintenance quantity must be a valid number',
            'quantity_for_maintenance.min' => 'Maintenance quantity cannot be negative',
            'price_per_unit.numeric' => 'Price per unit must be a valid number',
            'price_per_unit.min' => 'Price per unit cannot be negative',

            'maintenance_warning_date.date' => 'Maintenance warning date must be a valid date',
            'maintenance_date.date' => 'Maintenance date must be a valid date',

            'recurrence_unit.string' => 'Recurrence unit must be a valid text value',
            'recurrence_unit.max' => 'Recurrence unit cannot exceed 50 characters',

            'status_when_returned_id.exists' => 'The selected return status is invalid',
            'status_when_exceeded_id.exists' => 'The selected exceeded status is invalid',
        ];
    }
}
