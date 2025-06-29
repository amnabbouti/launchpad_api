<?php

namespace App\Http\Requests;

class MaintenanceDetailRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    protected function getValidationRules(): array
    {
        $detailId = $this->route('maintenance_detail')?->id ?? null;

        return [
            'org_id' => 'required|exists:organizations,id',
            'maintenance_condition_id' => 'required|exists:maintenance_conditions,id',
            'maintenance_id' => 'required|exists:maintenances,id',
            'value' => 'required|numeric',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'maintenance_condition_id.required' => 'The maintenance condition is required',
            'maintenance_condition_id.exists' => 'The selected maintenance condition is invalid',
            'maintenance_id.required' => 'The maintenance record is required',
            'maintenance_id.exists' => 'The selected maintenance record is invalid',
            'value.required' => 'The value is required',
            'value.numeric' => 'The value must be a number',
        ];
    }
}
