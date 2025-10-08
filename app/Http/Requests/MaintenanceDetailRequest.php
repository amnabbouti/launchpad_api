<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\ValidationMessages;

class MaintenanceDetailRequest extends BaseRequest {
    /**
     * error messages.
     */
    public function messages(): array {
        return [
            'maintenance_condition_id.required' => __(ValidationMessages::MAINTENANCE_CONDITION_FIELD_REQUIRED),
            'maintenance_condition_id.exists'   => __(ValidationMessages::MAINTENANCE_CONDITION_NOT_FOUND),
            'maintenance_id.required'           => __(ValidationMessages::MAINTENANCE_FIELD_REQUIRED),
            'maintenance_id.exists'             => __(ValidationMessages::MAINTENANCE_NOT_FOUND),
            'value.required'                    => __(ValidationMessages::MAINTENANCE_DETAIL_VALUE_REQUIRED),
            'value.numeric'                     => __(ValidationMessages::MAINTENANCE_DETAIL_VALUE_NUMERIC),
        ];
    }

    /**
     * validation rules.
     */
    protected function getValidationRules(): array {
        $detailId = $this->route('maintenance_detail')?->id ?? null;

        return [
            'org_id'                   => 'nullable|exists:organizations,id',
            'maintenance_condition_id' => 'required|exists:maintenance_conditions,id',
            'maintenance_id'           => 'required|exists:maintenances,id',
            'value'                    => 'required|numeric',
        ];
    }
}
