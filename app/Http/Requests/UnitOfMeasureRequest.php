<?php

namespace App\Http\Requests;

use App\Models\UnitOfMeasure;

class UnitOfMeasureRequest extends BaseRequest
{
    // Rules
    public function rules(): array
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
            'is_active' => 'boolean',
        ];
    }

    // Messages
    public function messages(): array
    {
        return [
            'name.required' => 'The unit name is required',
            'type.required' => 'The unit type is required',
            'type.in' => 'The selected unit type is invalid',
        ];
    }
}
