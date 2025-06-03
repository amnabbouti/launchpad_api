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
        $type = $this->query('type', 'status');
        $statusId = $this->route('status')?->id ?? $this->status_id ?? null;

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'org_id' => 'required|exists:organizations,id',
        ];

        // Add code validation rules for ItemStatus type
        if ($type === 'item-status') {
            $rules['code'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('item_statuses')->ignore($statusId)->where(fn ($query) => $query->where('org_id', $this->org_id)),
            ];
        }

        return $rules;
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        $type = $this->query('type', 'status');

        $messages = [
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
            'name.required' => 'The status name is required',
            'name.string' => 'The status name must be a string',
            'name.max' => 'The status name may not be greater than 255 characters',
            'description.string' => 'The description must be a string',
            'is_active.boolean' => 'The active status must be true or false',
        ];

        // Error messages for ItemStatus
        if ($type === 'item-status') {
            $messages['code.required'] = 'The status code is required';
            $messages['code.string'] = 'The status code must be a string';
            $messages['code.max'] = 'The status code may not be greater than 255 characters';
            $messages['code.unique'] = 'This status code already exists in your organization';
        }

        return $messages;
    }
}
