<?php

namespace App\Http\Requests;

class LocationRequest extends BaseRequest
{
    // validation rules
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:locations,id',
            // path is auto-generated
        ];
    }

    // Custom messages for validation errors
    public function messages(): array
    {
        return [
            'name.required' => 'The location name is required',
            'parent_id.exists' => 'The selected parent location does not exist',
        ];
    }
}
