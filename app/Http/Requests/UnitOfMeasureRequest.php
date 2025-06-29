<?php

namespace App\Http\Requests;

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
            'name.required' => 'The unit name is required',
            'type.required' => 'The unit type is required',
            'type.in' => 'The selected unit type is invalid',
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
        ];
    }
}
