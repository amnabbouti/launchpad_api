<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StatusRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $statusId = $this->route('status')?->id ?? $this->status_id ?? null;

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'org_id' => 'required|exists:organizations,id',
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('statuses')->ignore($statusId)->where(fn ($query) => $query->where('org_id', $this->org_id)),
            ],
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
            'name.required' => 'The status name is required',
            'name.string' => 'The status name must be a string',
            'name.max' => 'The status name may not be greater than 255 characters',
            'description.string' => 'The description must be a string',
            'is_active.boolean' => 'The active status must be true or false',
            'code.required' => 'The status code is required',
            'code.string' => 'The status code must be a string',
            'code.max' => 'The status code may not be greater than 255 characters',
            'code.unique' => 'This status code already exists in your organization',
        ];
    }
}
