<?php

namespace App\Http\Requests;

use App\Constants\ErrorMessages;
use App\Models\UnitOfMeasure;

class UnitOfMeasureRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'symbol' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'type' => 'required|string|in:'.implode(',', [
                UnitOfMeasure::TYPE_DATE,
                UnitOfMeasure::TYPE_DAYS_ACTIVE,
                UnitOfMeasure::TYPE_DAYS_CHECKED_OUT,
                UnitOfMeasure::TYPE_QUANTITY,
                UnitOfMeasure::TYPE_DISTANCE,
                UnitOfMeasure::TYPE_WEIGHT,
                UnitOfMeasure::TYPE_VOLUME,
            ]),
            'is_active' => 'nullable|boolean',
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => __(ErrorMessages::UNIT_OF_MEASURE_NAME_REQUIRED),
            'type.required' => __(ErrorMessages::UNIT_OF_MEASURE_TYPE_REQUIRED),
            'type.in' => __(ErrorMessages::UNIT_OF_MEASURE_TYPE_INVALID),
            'org_id.required' => __(ErrorMessages::UNIT_OF_MEASURE_ORG_REQUIRED),
            'org_id.exists' => __('The selected organization does not exist'),
        ];
    }
}
