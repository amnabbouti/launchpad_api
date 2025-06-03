<?php

namespace App\Http\Requests;

use App\Models\UnitOfMeasure;

class UnitOfMeasureRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $unitId = $this->route('unit_of_measure')?->id ?? $this->unit_of_measure_id ?? $this->unit_id ?? null;

        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:unit_of_measures,code,'.$unitId.',id,org_id,'.auth()->user()->org_id,
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
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The unit name is required',
            'code.unique' => 'This unit code is already used in your organization.',
            'type.required' => 'The unit type is required',
            'type.in' => 'The selected unit type is invalid',
        ];
    }
}
